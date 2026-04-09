<?php

namespace Taurus\Workflow\Services;

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
    protected string $module;

    protected int|string $recordIdentifier;

    protected array $selectedActions;

    protected array $actionsConfig;

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
        $this->logPrefix = 'MANUAL WORKFLOW';
        $this->initializeServices();
    }

    /**
     * Execute all selected workflow actions.
     */
    public function dispatch(): bool
    {
        if (empty($this->selectedActions)) {
            \Log::error('MANUAL WORKFLOW - No actions selected.');

            return false;
        }

        $jobWorkflowId = $this->createJobWorkflowEntry(null);
        if (! $jobWorkflowId) {
            return false;
        }

        setModuleForCurrentWorkflow($this->module);
        setRunningJobWorkflowId($jobWorkflowId);
        setRunningWorkflowId(null);

        \Log::info('MANUAL WORKFLOW - Starting execution', [
            'module' => $this->module,
            'recordIdentifier' => $this->recordIdentifier,
            'selectedActions' => $this->selectedActions,
        ]);

        foreach ($this->selectedActions as $actionType) {
            $actionPayload = $this->actionsConfig[$actionType] ?? null;

            if (! $actionPayload) {
                \Log::error("MANUAL WORKFLOW - No config found for action: {$actionType}");

                continue;
            }

            // Create action via factory
            $actionToExecute = null;
            try {
                $actionToExecute = $this->createAndInitializeAction($actionType, $actionPayload);
            } catch (\InvalidArgumentException $e) {
                \Log::error("MANUAL WORKFLOW - {$e->getMessage()}");

                continue;
            } catch (\Exception $e) {
                \Log::error("MANUAL WORKFLOW - Error initiating {$actionType} action: ".$e->getMessage());

                continue;
            }

            // Resolve data requirements via shared method
            try {
                [$listOfRequiredData, $listOfMandateData] = $this->resolveActionDataRequirements(
                    $actionToExecute,
                    $actionType,
                    $actionPayload
                );
            } catch (\Exception $e) {
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

                $parsedData = $this->fetchAndParseGraphQLData(
                    $this->module,
                    $listOfRequiredData,
                    $graphQLQuery
                );

                $data[] = $parsedData;
            } catch (\Exception $e) {
                \Log::error('MANUAL WORKFLOW - Error executing GraphQL query: '.$e->getMessage());

                continue;
            }

            if (config('app.env') != 'production') {
                \Log::info('MANUAL WORKFLOW - Resolved data: ', $data);
            }

            // Validate mandate data and resolve email address, then execute
            try {
                $hasPriorDataForWorkflow = $this->validateAndResolveData(
                    $data,
                    $listOfMandateData,
                    $actionType,
                    $actionPayload,
                    null,
                    $jobWorkflowId
                );

                if ($hasPriorDataForWorkflow === false && count($data) == 0) {
                    continue;
                }

                $actionToExecute->setWorkflowData(0, $jobWorkflowId, $this->recordIdentifier);
                $actionToExecute->setDataForAction('', $data);
                $actionToExecute->execute();
            } catch (\Exception $e) {
                \Log::error('MANUAL WORKFLOW - Error while executing action '.$actionType.': '.$e->getMessage());

                continue;
            }
        }

        return true;
    }
}
