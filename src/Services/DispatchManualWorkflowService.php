<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Log;
use Taurus\Workflow\Models\WorkflowLog;

/**
 * Class DispatchManualWorkflowService
 *
 * Executes workflow actions on-the-fly without a pre-configured saved workflow.
 * Unlike DispatchWorkflowService (which loads workflow config from the DB),
 * this service receives action configs directly from the caller (e.g. an API request).
 *
 * @property int $workflowId Always 0; there is no saved workflow behind a manual run.
 * @property string $module Module the targeted record belongs to, used to resolve its GraphQL mapping.
 * @property array $selectedActions Action types to execute, in order.
 * @property array $actionsConfig Action configuration keyed by action type.
 */
class DispatchManualWorkflowService extends AbstractDispatchService
{
    /**
     * Action types a manual run is allowed to execute.
     */
    private const MANUAL_ACTION_TYPES = ['EMAIL', 'WORKFLOW_OUTPUT'];

    protected int $workflowId = 0;

    protected string $module;

    protected array $selectedActions;

    protected array $actionsConfig;

    protected string $logPrefix = 'MANUAL WORKFLOW';

    /**
     * @param  string  $module  Module name (e.g. 'policy')
     * @param  int|string  $recordIdentifier  The record ID to resolve placeholders for
     * @param  array  $selectedActions  List of action types to execute (e.g. ['EMAIL'])
     * @param  array  $actionsConfig  Config keyed by action type (e.g. ['EMAIL' => [...]])
     * @param  int|string|null  $userId  The user who triggered the run
     */
    public function __construct(
        string $module,
        int|string $recordIdentifier,
        array $selectedActions,
        array $actionsConfig,
        int|string|null $userId = null
    ) {
        $this->module = $module;
        $this->recordIdentifier = $recordIdentifier;
        $this->selectedActions = $selectedActions;
        $this->actionsConfig = $actionsConfig;
        $this->userId = $userId;
        $this->initializeServices();
    }

    /**
     * Execute all selected workflow actions.
     */
    public function dispatch(): array
    {
        $actionResults = [];

        if (empty($this->selectedActions)) {
            Log::error("{$this->logPrefix} - No actions selected.");

            return ['success' => false, 'jobWorkflowId' => null, 'results' => $actionResults];
        }

        // workflow_id stays null: there is no saved workflow behind a manual run.
        $jobWorkflowId = $this->createJobWorkflowEntry(null);
        if (! $jobWorkflowId) {
            return ['success' => false, 'jobWorkflowId' => null, 'results' => $actionResults];
        }

        // Clear any workflow id left over from a previously dispatched workflow.
        setRunningWorkflowId(null);
        setModuleForCurrentWorkflow($this->module);

        Log::info("{$this->logPrefix} - Starting execution", [
            'module' => $this->module,
            'recordIdentifier' => $this->recordIdentifier,
            'selectedActions' => $this->selectedActions,
        ]);

        foreach ($this->selectedActions as $actionType) {
            $result = $this->processAction($actionType, $jobWorkflowId);

            if ($result !== null) {
                $actionResults[$actionType] = $result;
            }
        }

        WorkflowLog::markWorkflowCompleted($this->workflowId, $jobWorkflowId);

        return [
            'success' => true,
            'jobWorkflowId' => $jobWorkflowId,
            'results' => $actionResults,
        ];
    }

    /**
     * Runs a single selected action end to end: instantiates it, resolves its
     * placeholders via GraphQL, validates the result and executes it.
     *
     * Returns the action's execution result, or null when the action was skipped
     * at any step (the reason is always logged).
     */
    private function processAction(string $actionType, int $jobWorkflowId): mixed
    {
        $actionPayload = $this->actionsConfig[$actionType] ?? null;

        $this->createInProgressLog(
            $this->workflowId,
            $jobWorkflowId,
            $this->module,
            $actionType,
            $actionPayload
        );

        if (! $actionPayload) {
            $this->workflowService->addWorkflowLog(
                $this->workflowId,
                $jobWorkflowId,
                'EMPTY_ACTION_CONFIG',
                'No config found for action: '.$actionType
            );
            Log::error("{$this->logPrefix} - No config found for action: {$actionType}");

            return null;
        }

        // Instantiate and initialise the action class
        try {
            $actionToExecute = $this->instantiateAction(
                $actionType,
                $actionPayload,
                $this->module,
                $this->workflowId,
                $jobWorkflowId,
                self::MANUAL_ACTION_TYPES
            );
        } catch (\RuntimeException $e) {
            // Initialisation failed and has already been logged.
            return null;
        }

        if (! $actionToExecute) {
            Log::error("{$this->logPrefix} - Unsupported action type: {$actionType}");

            return null;
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
            Log::error("{$this->logPrefix} - Error getting required data for ".$actionType.': '.$e->getMessage());

            return null;
        }

        $data = $this->resolvePlaceholderData($listOfRequiredData, $jobWorkflowId);
        if ($data === null) {
            return null;
        }

        if (config('app.env') != 'production') {
            Log::info("{$this->logPrefix} - Resolved data: ", $data);
        }

        return $this->executeAction($actionToExecute, $data, $listOfMandateData, $actionType, $actionPayload, $jobWorkflowId);
    }

    /**
     * Fetches placeholder values for the record from GraphQL and parses them into a
     * single data row.
     *
     * A manual run always targets exactly one record, so the first record of a
     * paginated response is used. Returns null when the query or the parsing failed.
     */
    private function resolvePlaceholderData(array $listOfRequiredData, int $jobWorkflowId): ?array
    {
        // Fetch placeholder values from GraphQL using the record identifier
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
            Log::error("{$this->logPrefix} - Error executing GraphQL query: ".$e->getMessage());

            return null;
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
            return null;
        }

        $moduleClassForGraphQL = $queryResult['moduleClassForGraphQL'];
        $queryName = $queryResult['queryName'];
        $queryRootNode = $queryResult['response'][$queryName] ?? [];

        $record = $moduleClassForGraphQL->supportsPagination()
            ? ($queryRootNode['data'][0] ?? [])
            : $queryRootNode;

        // Parse placeholder values from GraphQL response
        try {
            $parsedData = $this->parseGraphQLResponse(
                [$queryName => $record],
                $listOfRequiredData,
                $queryResult['fieldMapping'],
                $moduleClassForGraphQL,
                $queryResult['graphQLSchemaBuilder'],
                $this->workflowId,
                $jobWorkflowId
            );
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog(
                $this->workflowId,
                $jobWorkflowId,
                'GRAPHQL_ERROR',
                $e->getMessage()
            );
            Log::error("{$this->logPrefix} - Error parsing GraphQL response: ".$e->getMessage());

            return null;
        }

        return [$parsedData];
    }

    /**
     * Validates the resolved rows against the action's mandatory fields, resolves the
     * email recipient where applicable, and runs the action.
     *
     * Returns the action's result, or null when there was nothing valid to send or the
     * execution failed.
     */
    private function executeAction(
        $actionToExecute,
        array $data,
        array $listOfMandateData,
        string $actionType,
        array $actionPayload,
        int $jobWorkflowId
    ): mixed {
        // Validate mandate data and resolve email address, then execute
        try {
            $data = $this->validateAndFilterData(
                $data,
                $listOfMandateData,
                $actionPayload,
                $actionType,
                $this->workflowId,
                $jobWorkflowId
            );

            if ($data === false) {
                return null;
            }

            $actionToExecute->setWorkflowData($this->workflowId, $jobWorkflowId, $this->recordIdentifier);
            $actionToExecute->setDataForAction('', $data);

            return $actionToExecute->execute();
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog(
                $this->workflowId,
                $jobWorkflowId,
                'ERROR_EXECUTING_ACTION',
                $e->getMessage()
            );
            Log::error("{$this->logPrefix} - Error while executing action ".$actionType.': '.$e->getMessage());

            return null;
        }
    }
}
