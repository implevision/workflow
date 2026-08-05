<?php

namespace Taurus\Workflow\Services;

use Taurus\Workflow\Models\WorkflowLog;

/**
 * Class DispatchManualWorkflowService
 *
 * Executes workflow actions on-the-fly without a pre-configured saved workflow.
 * Unlike DispatchWorkflowService (which loads workflow config from the DB),
 * this service receives action configs directly from the caller (e.g. an API request).
 *
 * Placeholder values are still resolved via GraphQL using the provided
 * module + recordIdentifier, same as the standard workflow engine.
 */
class DispatchManualWorkflowService extends AbstractDispatchService
{
    protected int $workflowId = 0;

    protected string $module;

    protected int|string $recordIdentifier;

    protected array $selectedActions;

    protected array $actionsConfig;

    protected string $logPrefix = 'MANUAL WORKFLOW';

    /**
     * @param  string  $module  Module name (e.g. 'policy')
     * @param  int|string  $recordIdentifier  The record ID to resolve placeholders for
     * @param  array  $selectedActions  List of action types to execute (e.g. ['EMAIL'])
     * @param  array  $actionsConfig  Config keyed by action type (e.g. ['EMAIL' => [...]])
     */
    public function __construct(
        string $module,
        int|string $recordIdentifier,
        array $selectedActions,
        array $actionsConfig
    ) {
        $this->module = $module;
        $this->recordIdentifier = $recordIdentifier;
        $this->selectedActions = $selectedActions;
        $this->actionsConfig = $actionsConfig;
        $this->initializeServices();
    }

    /**
     * Execute all selected workflow actions.
     */
    public function dispatch(): array
    {
        $actionResults = [];

        if (empty($this->selectedActions)) {
            \Log::error('MANUAL WORKFLOW - No actions selected.');

            return ['success' => false, 'jobWorkflowId' => null, 'results' => $actionResults];
        }

        $jobWorkflowId = $this->createJobWorkflowEntry(null);
        if (! $jobWorkflowId) {
            return ['success' => false, 'jobWorkflowId' => null, 'results' => $actionResults];
        }
        setRunningWorkflowId(null);

        setModuleForCurrentWorkflow($this->module);
        setRunningJobWorkflowId($jobWorkflowId);

        \Log::info('MANUAL WORKFLOW - Starting execution', [
            'module' => $this->module,
            'recordIdentifier' => $this->recordIdentifier,
            'selectedActions' => $this->selectedActions,
        ]);

        foreach ($this->selectedActions as $actionType) {
            $actionPayload = $this->actionsConfig[$actionType] ?? null;

            WorkflowLog::create([
                'job_workflow_id' => $jobWorkflowId ?: null,
                'workflow_id' => $this->workflowId,
                'record_identifier' => $this->recordIdentifier ?? null,
                'module' => $this->module,
                'status' => WorkflowLog::STATUS_IN_PROGRESS,
                'action_type' => $actionType,
            ]);

            if (! $actionPayload) {
                $this->workflowService->addWorkflowLog(
                    $this->workflowId,
                    $jobWorkflowId,
                    'EMPTY_ACTION_CONFIG',
                    'No config found for action: '.$actionType
                );
                \Log::error("MANUAL WORKFLOW - No config found for action: {$actionType}");

                continue;
            }

            $action = ['actionType' => $actionType, 'payload' => $actionPayload];

            try {
                $actionToExecute = $this->instantiateAction(
                    $actionType,
                    $actionPayload,
                    $this->module,
                    $this->workflowId,
                    $jobWorkflowId,
                    ['EMAIL', 'WORKFLOW_OUTPUT']
                );
            } catch (\RuntimeException $e) {
                continue;
            }

            if (! $actionToExecute) {
                continue;
            }

            // Determine placeholders required by the action
            try {
                [$listOfRequiredData, $listOfMandateData] = $this->resolveActionDataRequirements(
                    $actionToExecute,
                    $actionType,
                    $actionPayload
                );
            } catch (\Exception $e) {
                $this->workflowService->addWorkflowLog(
                    $this->workflowId,
                    $jobWorkflowId,
                    'ERROR_GETTING_REQUIRED_DATA',
                    $e->getMessage()
                );
                \Log::error('MANUAL WORKFLOW - Error getting required data for '.$actionType.': '.$e->getMessage());

                continue;
            }

            // Fetch placeholder values from GraphQL using the record identifier
            $data = [];
            try {
                $graphQLQuery = $this->workflowService->getQueryForRecordIdentifier(
                    $this->module,
                    $this->recordIdentifier
                );
            } catch (\Exception $e) {
                $this->workflowService->addWorkflowLog(
                    $this->workflowId,
                    $jobWorkflowId,
                    'GRAPHQL_ERROR',
                    $e->getMessage()
                );
                \Log::error('MANUAL WORKFLOW - Error executing GraphQL query: '.$e->getMessage());

                continue;
            }

            $queryResult = $this->buildAndExecuteGraphQLQuery(
                $this->module,
                [],
                $listOfRequiredData,
                $graphQLQuery,
                false,
                $this->workflowId,
                $jobWorkflowId
            );

            if ($queryResult === null) {
                continue;
            }

            // Parse placeholder values from GraphQL response
            try {
                $parsedData = $this->parseGraphQLResponse(
                    $queryResult['response'],
                    $listOfRequiredData,
                    $queryResult['fieldMapping'],
                    $queryResult['moduleClassForGraphQL'],
                    $queryResult['graphQLSchemaBuilder'],
                    $this->workflowId,
                    $jobWorkflowId
                );

                $data[] = $parsedData;
            } catch (\Exception $e) {
                $this->workflowService->addWorkflowLog(
                    $this->workflowId,
                    $jobWorkflowId,
                    'GRAPHQL_ERROR',
                    $e->getMessage()
                );
                \Log::error('MANUAL WORKFLOW - Error parsing GraphQL response: '.$e->getMessage());

                continue;
            }

            if (config('app.env') != 'production') {
                \Log::info('MANUAL WORKFLOW - Resolved data: ', $data);
            }

            // Validate mandate data and resolve email address, then execute
            try {
                $data = $this->validateAndFilterData(
                    $data,
                    $listOfMandateData,
                    $action,
                    $actionType,
                    $this->workflowId,
                    $jobWorkflowId
                );

                if ($data === false) {
                    continue;
                }

                $actionToExecute->setWorkflowData($this->workflowId, $jobWorkflowId, $this->recordIdentifier);
                $actionToExecute->setDataForAction('', $data);
                $actionResults[$actionType] = $actionToExecute->execute();
            } catch (\Exception $e) {
                $this->workflowService->addWorkflowLog(
                    $this->workflowId,
                    $jobWorkflowId,
                    'ERROR_EXECUTING_ACTION',
                    $e->getMessage()
                );
                \Log::error('MANUAL WORKFLOW - Error while executing action '.$actionType.': '.$e->getMessage());

                continue;
            }
        }

        WorkflowLog::markWorkflowCompleted($this->workflowId, $jobWorkflowId);

        return [
            'success' => true,
            'jobWorkflowId' => $jobWorkflowId,
            'results' => $actionResults,
        ];
    }
}
