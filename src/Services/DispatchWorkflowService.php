<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Taurus\Workflow\Models\WorkflowLog;
use Taurus\Workflow\Repositories\Eloquent\WorkflowRepository;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

/**
 * Class DispatchWorkflowService
 *
 * Dispatches a workflow that was configured and saved beforehand: it loads the
 * workflow definition, walks its conditions, and runs each condition's actions
 * against the records the workflow targets.
 *
 * @property mixed|null $workflowInfo The workflow definition loaded from the database.
 * @property array $data Caller-supplied record data; when non-empty the run is treated as manually invoked.
 * @property array $appendPlaceHolders Extra placeholders to resolve, keyed by name; a non-empty value needs no lookup.
 * @property int $page Zero-based page of records this run processes, for paginated modules.
 * @property bool $isManuallyInvoked True when the run was handed its data instead of querying for it.
 * @property string|null $referenceId Optional external reference recorded against the job-workflow row.
 * @property WorkflowRepository $workflowRepo Repository used to reload the workflow when re-scheduling.
 * @property array|null $nextPageCommand Command to dispatch the next page, set once more pages are reported.
 * @property string $logPrefix Overrides the inherited prefix to tag this engine's log lines.
 */
class DispatchWorkflowService extends AbstractDispatchService
{
    // Signals returned by processAction to steer the dispatch loops.
    private const SIGNAL_SKIP_CONDITION = 'SKIP_CONDITION'; // skip the rest of this condition

    private const SIGNAL_NEXT_ACTION = 'NEXT_ACTION'; // abandon this action only

    private const SIGNAL_STOP_ALL = 'STOP_ALL'; // stop the whole dispatch

    private $workflowInfo = null;

    protected $data;

    protected $appendPlaceHolders;

    protected $page;

    protected $isManuallyInvoked = false;

    protected $referenceId;

    protected $workflowRepo;

    protected string $logPrefix = 'WORKFLOW';

    private ?array $nextPageCommand = null;

    /**
     * DispatchWorkflowService constructor.
     *
     * @param  int  $workflowId  The ID of the workflow to be dispatched.
     * @param  int|string  $recordIdentifier  An optional identifier for the record, default is 0.
     * @param  array  $data  Record data supplied by the caller; when non-empty the run is treated as manually invoked
     * @param  array  $appendPlaceHolders  Extra placeholders keyed by name; a non-empty value needs no lookup
     * @param  string|null  $referenceId  Optional external reference for the job-workflow row
     * @param  int  $page  Zero-based page of records to process, for paginated modules
     * @param  int|string|null  $userId  The user who triggered the run
     */
    public function __construct(
        int $workflowId,
        int|string $recordIdentifier = 0,
        $data = [],
        $appendPlaceHolders = [],
        ?string $referenceId = null,
        int $page = 0,
        int|string|null $userId = null
    ) {
        $this->workflowId = $workflowId;
        $this->initializeServices();
        $this->workflowRepo = app(WorkflowRepository::class);
        $this->recordIdentifier = $recordIdentifier;
        $this->data = $data;
        $this->appendPlaceHolders = $appendPlaceHolders;
        $this->page = $page;
        $this->isManuallyInvoked = count($data) ? true : false;
        $this->referenceId = $referenceId;
        $this->userId = $userId;
        $this->getInfo();
        // getInfo() leaves workflowInfo null when the lookup fails; dispatch() bails
        // on that before anything reads the module.
        $this->module = $this->workflowInfo['detail']['module'] ?? '';
    }

    /**
     * Retrieves information related to the dispatch workflow.
     *
     * This method is responsible for fetching and returning the necessary
     * information that is pertinent to the dispatch workflow process.
     *
     * @return mixed Returns the information related to the dispatch workflow.
     */
    public function getInfo()
    {
        try {
            $workflowInfo = $this->workflowService->getWorkflowDetailsById($this->workflowId);
        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} - Error fetching workflow details: ".$e->getMessage());

            return false;
        }

        $this->workflowInfo = $workflowInfo->toArray();
    }

    /**
     * Dispatches the workflow process.
     *
     * This method is responsible for initiating the workflow dispatching
     * process. It may involve various steps such as validating input,
     * executing the workflow logic, and handling any exceptions that may
     * arise during the dispatching process.
     *
     *
     * @throws WorkflowException If there is an error during the dispatching process.
     */
    public function dispatch(): bool
    {
        if (! $this->workflowId || ! is_array($this->workflowInfo)) {
            return false;
        }

        $this->rescheduleWithinEventWorkflow();

        if (! $this->isWorkflowActive()) {
            return false;
        }

        Log::info("{$this->logPrefix} - Name: ".$this->workflowInfo['detail']['name']);

        if (! $this->createJobWorkflowEntry($this->workflowId, $this->referenceId)) {
            return false;
        }

        setModuleForCurrentWorkflow($this->module);

        $this->nextPageCommand = null;
        $graphQLQuery = $this->buildBaseGraphQLQuery();

        foreach ($this->workflowInfo['workFlowConditions'] as $condition) {
            if (isset($condition['status']) && $condition['status'] === false) {
                Log::info("{$this->logPrefix} - Condition skipped (inactive): ".($condition['id'] ?? ''));

                continue;
            }

            $feedFile = $this->resolveFeedFile($condition);

            // NOTE: the merge intentionally persists into subsequent conditions,
            // matching the original behaviour.
            $graphQLQuery = $this->mergeConditionRules($condition, $graphQLQuery);

            foreach ($condition['instanceActions'] as $action) {
                $signal = $this->processAction($action, $graphQLQuery, $feedFile);

                if ($signal === self::SIGNAL_SKIP_CONDITION) {
                    continue 2;
                }

                if ($signal === self::SIGNAL_STOP_ALL) {
                    break 2;
                }
            }

            WorkflowLog::markWorkflowCompleted($this->workflowId, $this->jobWorkflowId);
        }

        $this->dispatchNextPage();

        return true;
    }

    /**
     * Re-schedules the workflow when its execution event is configured as WITH_IN,
     * so the follow-up occurrences are queued before this run proceeds.
     */
    private function rescheduleWithinEventWorkflow(): void
    {
        if (
            empty($this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEventIncident'])
            || $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEventIncident'] != 'WITH_IN'
        ) {
            return;
        }

        $workflow = $this->workflowRepo->getById($this->workflowId)->toArray();
        $this->workflowService->scheduleWorkflows([$workflow]);
    }

    /**
     * Checks that the workflow is currently active.
     */
    private function isWorkflowActive(): bool
    {
        if ($this->workflowInfo['detail']['isActive'] == false) {
            Log::info("{$this->logPrefix} - Workflow is not active. Exiting.");

            return false;
        }

        return true;
    }

    /**
     * Builds the base GraphQL filter query from the effective-action configuration and
     * the record identifier.
     *
     * Both parts are optional. When both are present the effective-action query is
     * joined onto the record-identifier query with an AND. The method throws when
     * either sub-build fails so the caller can surface the error.
     *
     * @throws \Exception When a sub-query cannot be built.
     */
    private function buildBaseGraphQLQuery(): array
    {
        $effectiveActionQuery = $this->buildEffectiveActionQuery();

        if ($this->recordIdentifier && ! $this->isManuallyInvoked) {
            try {
                $graphQLQuery = $this->workflowService->getQueryForRecordIdentifier(
                    $this->module,
                    $this->recordIdentifier
                );

                if (count($effectiveActionQuery)) {
                    $graphQLQuery['JOIN'] = ['operator' => 'AND', 'condition' => [$effectiveActionQuery]];
                }

                return $graphQLQuery;
            } catch (\Exception $e) {
                throw new \Exception('Error while creating GraphQL query for record identifier. '.$e->getMessage());
            }
        }

        return $effectiveActionQuery;
    }

    /**
     * Builds the effective-action filter used when the workflow runs on a follow-up
     * event rather than a certain date/time.
     *
     * NEED TO FILTER DATA IF EFFECTIVE ACTION IS 'ON_DATE_TIME' AND EVENT CONFIGURED FOR FOLLOW UP EVENT
     *
     * Example: After/Before X day(s)/month(s)/year(s) of the event.
     * Returns an empty array when the workflow is not configured that way.
     *
     * @throws \Exception When the query cannot be built.
     */
    private function buildEffectiveActionQuery(): array
    {
        if (
            $this->isManuallyInvoked ||
            $this->workflowInfo['when']['effectiveActionToExecuteWorkflow'] != 'ON_DATE_TIME' ||
            $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['certainDateTime']
        ) {
            return [];
        }

        try {
            return $this->workflowService->getQueryForEffectiveAction(
                $this->module,
                $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionFrequency'],
                $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionFrequencyType'],
                $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEventIncident'],
                $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEvent']
            );
        } catch (\Exception $e) {
            throw new \Exception('Error while creating GraphQL query for effective action. '.$e->getMessage());
        }
    }

    /**
     * Downloads the custom-feed file from S3 when the condition targets a CUSTOM_FEED.
     * Returns an empty string for every other applyRuleTo value, and on download
     * failure (the error is logged and the workflow continues).
     */
    private function resolveFeedFile(array $condition): string
    {
        if ($condition['applyRuleTo'] == 'ALL') {
            // DO NOTHING
        }

        if ($condition['applyRuleTo'] != 'CUSTOM_FEED') {
            return '';
        }

        try {
            return $this->getFileOnLocal($condition['s3FilePath']);
        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} - Failed to download feed file from S3: ".$condition['s3FilePath']);
            Log::error("{$this->logPrefix} - ".$e->getMessage());

            return '';
        }
    }

    /**
     * Merges a CERTAIN condition's rules into the running GraphQL query.
     *
     * When the query already carries a JOIN the rules are appended to it; otherwise
     * they become the JOIN, or the whole query when nothing has been built yet.
     */
    private function mergeConditionRules(array $condition, array $graphQLQuery): array
    {
        if ($condition['applyRuleTo'] != 'CERTAIN' || $this->isManuallyInvoked) {
            return $graphQLQuery;
        }

        $conditionsToApply = GraphQLSchemaBuilderService::buildWhereConditionFromGroup($condition['applyConditionRules']);

        if (empty($conditionsToApply)) {
            return $graphQLQuery;
        }

        if (! count($graphQLQuery)) {
            return $conditionsToApply;
        }

        if (isset($graphQLQuery['JOIN'])) {
            $graphQLQuery['JOIN']['condition'][] = $conditionsToApply;
        } else {
            $graphQLQuery['JOIN'] = $conditionsToApply;
        }

        return $graphQLQuery;
    }

    /**
     * Processes a single action within a condition: instantiates it, resolves its data,
     * validates it and executes it.
     *
     * Returns a signal constant to control the outer dispatch loops, or null to carry on
     * with the next action.
     */
    private function processAction(array $action, array $graphQLQuery, string $feedFile): ?string
    {
        $actionType = $action['actionType'];
        $actionPayload = $action['payload'];

        $this->createInProgressLog($actionType);

        try {
            $actionToExecute = $this->instantiateAction($actionType, $actionPayload);
        } catch (\RuntimeException $e) {
            // Initialisation failed and has already been logged; skip the rest of this condition.
            return self::SIGNAL_SKIP_CONDITION;
        }

        if (! $actionToExecute) {
            Log::error("{$this->logPrefix} - Action not found: ".$actionType);

            return null;
        }

        // Placeholders data to extract from appendPlaceHolders
        [$placeHolderWithValues, $placeHolderToExtract] = $this->partitionPlaceholders($this->appendPlaceHolders);

        try {
            [$listOfRequiredData, $listOfMandateData] = $this->resolveActionDataRequirements(
                $actionToExecute,
                $actionType,
                $actionPayload,
                $placeHolderToExtract
            );
        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} - Error while getting required data for action - ".$actionType.' : '.$e->getMessage());

            return null;
        }

        if ($this->isManuallyInvoked) {
            $data = [$this->data];
        } elseif (count($graphQLQuery) || count($listOfRequiredData)) {
            $result = $this->fetchDataForAction(
                $graphQLQuery,
                $listOfRequiredData,
                $actionType,
                $placeHolderWithValues
            );

            // SIGNAL_NEXT_ACTION simply abandons this action; anything else
            // (SIGNAL_STOP_ALL) is propagated to the dispatch loops.
            if ($result['signal'] === self::SIGNAL_NEXT_ACTION) {
                return null;
            }

            if ($result['signal'] !== null) {
                return $result['signal'];
            }

            $data = $result['data'];
        } else {
            $data = [];
        }

        if (config('app.env') != 'production') {
            Log::info("{$this->logPrefix} - data: ", $data);
        }

        try {
            $data = $this->validateAndFilterData(
                $data,
                $listOfMandateData,
                $actionPayload,
                $actionType,
                $placeHolderToExtract
            );

            if ($data === false) {
                return null;
            }

            $actionToExecute->setWorkflowData($this->workflowId, $this->jobWorkflowId, $this->recordIdentifier);
            $actionToExecute->setDataForAction($feedFile, $data);
            $actionToExecute->execute();
        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} - Error while executing action - ".$actionType.' : '.$e->getMessage());

            return null;
        }

        return null;
    }

    /**
     * Runs the GraphQL query for an action and turns the response into action data rows.
     *
     * Returns ['data' => array, 'signal' => string|null].
     *
     * A non-null signal means the caller must stop:
     * `SIGNAL_NEXT_ACTION` when the query or extraction failed and this
     * action cannot run,
     * `SIGNAL_STOP_ALL` when the targeted record has no usable data.
     */
    private function fetchDataForAction(
        array $graphQLQuery,
        array $listOfRequiredData,
        string $actionType,
        array $placeHolderWithValues
    ): array {
        $queryResult = $this->buildAndExecuteGraphQLQuery(
            $this->appendPlaceHolders,
            $listOfRequiredData,
            $graphQLQuery,
            true,
            function ($moduleClassForGraphQL) {
                $moduleClassForGraphQL->setPage($this->page);
                $moduleClassForGraphQL->setQueryArgsContext(
                    $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow'] ?? []
                );

                return $this->page;
            }
        );

        if ($queryResult === null) {
            return ['data' => [], 'signal' => self::SIGNAL_NEXT_ACTION];
        }

        $moduleClassForGraphQL = $queryResult['moduleClassForGraphQL'];
        $response = $queryResult['response'];

        // If schema provides custom record extraction, use it directly (skip jqFilter)
        if ($moduleClassForGraphQL->hasCustomRecordExtraction()) {
            $data = [];
            foreach ($moduleClassForGraphQL->getRecordsFromResponse($response) as $record) {
                $data[] = array_merge($record, $placeHolderWithValues);
            }
        } else {
            if (empty(array_first($response))) {
                Log::warning("{$this->logPrefix} - GraphQL unable to fetch the data");

                return ['data' => [], 'signal' => self::SIGNAL_NEXT_ACTION];
            }

            $extraction = $this->extractRecordsFromResponse(
                $queryResult,
                $listOfRequiredData,
                $actionType,
                $placeHolderWithValues
            );

            if ($extraction['signal'] !== null) {
                return $extraction;
            }

            $data = $extraction['data'];
        }

        $this->captureNextPageCommand($moduleClassForGraphQL, $response, $queryResult['queryArgs']);

        return ['data' => $data, 'signal' => null];
    }

    /**
     * Parses every record in a GraphQL response into action data rows using the
     * configured jqFilter / parseResultCallback mapping.
     *
     * WEB_HOOK actions fan each parsed record out into one row per array value; every
     * other action type contributes one row per record.
     *
     * Returns ['data' => array, 'signal' => string|null], where the signal is
     * SIGNAL_STOP_ALL when a targeted record resolved to no usable values, and
     * SIGNAL_NEXT_ACTION when extraction threw.
     */
    private function extractRecordsFromResponse(
        array $queryResult,
        array $listOfRequiredData,
        string $actionType,
        array $placeHolderWithValues
    ): array {
        $moduleClassForGraphQL = $queryResult['moduleClassForGraphQL'];
        $queryName = $moduleClassForGraphQL->getQueryName();
        $queryRootNode = $queryResult['response'][$queryName] ?? [];

        $records = $moduleClassForGraphQL->supportsPagination()
            ? ($queryRootNode['data'] ?? [])
            : [$queryRootNode];

        $perRecordResponses = array_map(
            fn ($record) => [$queryName => $record],
            $records
        );

        $data = [];

        try {
            foreach ($perRecordResponses as $recordResponse) {
                $parsedData = $this->parseGraphQLResponse(
                    $recordResponse,
                    $listOfRequiredData,
                    $queryResult['fieldMapping'],
                    $moduleClassForGraphQL,
                    $queryResult['graphQLSchemaBuilder'],
                );

                $parsedData = array_merge($parsedData, $placeHolderWithValues);
                $hasAtLeastOneValue = ! empty(array_filter($parsedData, fn ($v) => $v !== null && $v !== '' && $v !== false && $v !== 'null'));

                if ($this->recordIdentifier && ! empty($parsedData) && ! $hasAtLeastOneValue) {
                    Log::warning("{$this->logPrefix} -  Data unavailable or all required fields are empty");

                    return ['data' => [], 'signal' => self::SIGNAL_STOP_ALL];
                }

                if ($actionType == 'WEB_HOOK') {
                    $data = array_merge($data, $this->generatePayloadFromParsedData($parsedData));
                } else {
                    // SET DATA FOR ACTION
                    $data[] = $parsedData;
                }
            }
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog(
                $this->workflowId,
                $this->jobWorkflowId,
                'GRAPHQL_ERROR',
                $e->getMessage()
            );
            Log::error(
                "{$this->logPrefix} - Error while extracting data from GraphQL response - ".$e->getMessage(),
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line_no' => $e->getLine(),
                ]
            );

            return ['data' => [], 'signal' => self::SIGNAL_NEXT_ACTION];
        }

        return ['data' => $data, 'signal' => null];
    }

    /**
     * Records the command that will dispatch the next page, the first time a response
     * reports that more pages are available.
     */
    private function captureNextPageCommand($moduleClassForGraphQL, array $response, array $queryArgs): void
    {
        if ($this->nextPageCommand !== null) {
            return;
        }

        $nextPageArgs = $moduleClassForGraphQL->getNextPageArgs($response, $queryArgs);

        if ($nextPageArgs === null) {
            return;
        }

        $this->nextPageCommand = gitCommandToDispatchWorkflow(
            $this->workflowId,
            $this->recordIdentifier,
            [],
            $this->appendPlaceHolders,
            $this->referenceId,
            $this->page + 1,
            $this->userId
        );
    }

    /**
     * Runs the queued next-page dispatch command, when the run produced one.
     */
    private function dispatchNextPage(): void
    {
        if ($this->nextPageCommand === null) {
            return;
        }

        Artisan::call($this->nextPageCommand['command'], $this->nextPageCommand['options']);
    }

    /**
     * Generates a payload from the parsed data.
     *
     * This function takes the parsed data as input and constructs
     * a payload that can be used for further processing or
     * transmission. The structure of the payload will depend on
     * the specific requirements of the workflow.
     *
     * @param  mixed  $parsedData  The data that has been parsed and
     *                             is to be converted into a payload.
     * @return array The generated payload based on the parsed data.
     */
    private function generatePayloadFromParsedData($parsedData)
    {
        $totalPayloadToGenerate = 0;

        foreach ($parsedData as $key => $value) {
            if (is_array($value)) {
                $totalPayloadToGenerate = max($totalPayloadToGenerate, count($value));
            }
        }

        if (! $totalPayloadToGenerate) {
            // All values are scalars wrap so callers always get an array-of-rows,
            // consistent with the multi-value path below.
            return [$parsedData];
        }

        $payload = [];
        foreach ($parsedData as $key => $value) {
            if (! is_array($value)) {
                for ($i = 0; $i < $totalPayloadToGenerate; $i++) {
                    $payload[$i][$key] = $value;
                }
            } else {
                for ($i = 0; $i < $totalPayloadToGenerate; $i++) {
                    $payload[$i][$key] = $value[$i];
                }
            }
        }

        return $payload;
    }
}
