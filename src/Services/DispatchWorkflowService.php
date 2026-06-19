<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Storage;
use Taurus\Workflow\Models\WorkflowLog;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Services\AWS\S3;
use Taurus\Workflow\Services\GraphQL\Client as GraphQLClient;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;
use Taurus\Workflow\Services\WorkflowActions\EmailAction;
use Taurus\Workflow\Services\WorkflowActions\WebhookAction;
use Taurus\Workflow\Services\WorkflowActions\WorkflowOutputAction;

/**
 * Class DispatchWorkflowService
 *
 * This class is responsible for managing the dispatch workflow.
 * It handles the workflow ID, workflow information, and interacts
 * with the job workflow repository and workflow service.
 *
 * @property int $workflowId The ID of the workflow.
 * @property mixed|null $workflowInfo Information related to the workflow.
 * @property JobWorkflowRepository $jobWorkflowRepo Repository for job workflows.
 * @property WorkflowService $workflowService Service for managing workflows.
 * @property bool $isWorkflowLive Indicates if the workflow is currently live.
 * @property string $recordIdentifier Identifier for the record associated with the workflow.
 */
class DispatchWorkflowService
{
    private const SIGNAL_SKIP_CONDITION = 'SKIP_CONDITION';

    private const SIGNAL_STOP_ALL = 'STOP_ALL';

    private $workflowId;

    private $workflowInfo = null;

    protected $jobWorkflowRepo;

    protected $workflowService;

    protected $isWorkflowLive;

    protected $recordIdentifier;

    protected $data;

    protected $appendPlaceHolders;

    protected $isManuallyInvoked = false;

    protected $referenceId;

    /**
     * DispatchWorkflowService constructor.
     *
     * @param  int  $workflowId  The ID of the workflow to be dispatched.
     * @param  int|string  $recordIdentifier  An optional identifier for the record, default is 0.
     */
    public function __construct(int $workflowId, int|string $recordIdentifier = 0, $data = [], $appendPlaceHolders = [], ?string $referenceId = null)
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowRepo = app(JobWorkflowRepository::class);
        $this->workflowService = app(WorkflowService::class);
        $this->recordIdentifier = $recordIdentifier;
        $this->data = $data;
        $this->appendPlaceHolders = $appendPlaceHolders;
        $this->isManuallyInvoked = count($data) ? true : false;
        $this->referenceId = $referenceId;
        $this->getInfo();
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
            \Log::error('WORKFLOW - Error fetching workflow details: '.$e->getMessage());

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
     * @return void
     *
     * @throws WorkflowException If there is an error during the dispatching process.
     */
    public function dispatch()
    {
        if (! $this->validateDispatch()) {
            return false;
        }

        $jobWorkflowId = $this->createJobWorkflow();
        if (! $jobWorkflowId) {
            return false;
        }

        setModuleForCurrentWorkflow($this->workflowInfo['detail']['module']);

        $graphQLQuery = $this->buildBaseGraphQLQuery();
        $allConditions = $this->workflowInfo['workFlowConditions'];

        foreach ($allConditions as $condition) {
            if (isset($condition['status']) && $condition['status'] === false) {
                \Log::info('WORKFLOW - Condition skipped (inactive): '.($condition['id'] ?? ''));

                continue;
            }

            $feedFile = $this->resolveFeedFile($condition);

            // Preserve original bleed behaviour: CERTAIN rules accumulate across conditions
            $graphQLQuery = $this->mergeConditionRules($condition, $graphQLQuery);

            foreach ($condition['instanceActions'] as $action) {
                $signal = $this->processAction($action, $graphQLQuery, $jobWorkflowId, $feedFile);
                if ($signal === self::SIGNAL_SKIP_CONDITION) {
                    continue 2;
                }
                if ($signal === self::SIGNAL_STOP_ALL) {
                    break 2;
                }
            }

            WorkflowLog::markWorkflowCompleted($this->workflowId, $jobWorkflowId);
        }

        return true;
    }

    /**
     * Checks that the workflow exists, is loaded, and is currently active.
     */
    private function validateDispatch(): bool
    {
        if (! $this->workflowId || ! is_array($this->workflowInfo)) {
            return false;
        }

        if ($this->workflowInfo['detail']['isActive'] == false) {
            \Log::info('WORKFLOW - Workflow is not active. Exiting.');

            return false;
        }

        \Log::info('WORKFLOW - Name: '.$this->workflowInfo['detail']['name']);

        return true;
    }

    /**
     * Creates a job-workflow record in the database and registers its ID globally.
     * Returns 0 (falsy) on failure so the caller can short-circuit.
     */
    private function createJobWorkflow(): int
    {
        try {
            $jobWorkflow = [
                'workflow_id' => $this->workflowId,
                'status' => 'CREATED',
                'total_no_of_records_to_execute' => 0,
                'total_no_of_records_executed' => 0,
                'response' => [],
            ];
            if ($this->referenceId !== null) {
                $jobWorkflow['reference_id'] = $this->referenceId;
            }
            $jobWorkflowId = $this->jobWorkflowRepo->createSingle($jobWorkflow);
            setRunningJobWorkflowId($jobWorkflowId);

            return $jobWorkflowId;
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error while creating entry in JOB WORKFLOW table. '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Builds the base GraphQL filter query from the effective-action config and
     * the record identifier. Both parts are optional; the method throws when either
     * sub-build fails so the caller can surface the error.
     */
    private function buildBaseGraphQLQuery(): array
    {
        $graphQLQuery = [];

        if (
            ! $this->isManuallyInvoked &&
            $this->workflowInfo['when']['effectiveActionToExecuteWorkflow'] == 'ON_DATE_TIME' &&
            ! $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['certainDateTime']
        ) {
            try {
                $graphQLQuery = $this->workflowService->getQueryForEffectiveAction(
                    $this->workflowInfo['detail']['module'],
                    $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionFrequency'],
                    $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionFrequencyType'],
                    $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEventIncident'],
                    $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEvent']
                );
            } catch (\Exception $e) {
                throw new \Exception('Error while creating GraphQL query for effective action. '.$e->getMessage());
            }
        }

        if ($this->recordIdentifier && ! $this->isManuallyInvoked) {
            try {
                $queryToAppend = $this->workflowService->getQueryForRecordIdentifier(
                    $this->workflowInfo['detail']['module'],
                    $this->recordIdentifier
                );
                if (count($graphQLQuery)) {
                    $graphQLQuery['JOIN'] = ['operator' => 'AND', 'condition' => $queryToAppend];
                } else {
                    $graphQLQuery = $queryToAppend;
                }
            } catch (\Exception $e) {
                throw new \Exception('Error while creating GraphQL query for record identifier. '.$e->getMessage());
            }
        }

        return $graphQLQuery;
    }

    /**
     * Downloads the custom-feed file from S3 to local storage when the condition
     * targets a CUSTOM_FEED. Returns an empty string for all other applyRuleTo values
     * and on download failure (error is logged).
     */
    private function resolveFeedFile(array $condition): string
    {
        if ($condition['applyRuleTo'] !== 'CUSTOM_FEED') {
            return '';
        }

        try {
            return $this->getFileOnLocal($condition['s3FilePath']);
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Failed to download feed file from S3: '.$condition['s3FilePath']);
            \Log::error('WORKFLOW - '.$e->getMessage());

            return '';
        }
    }

    /**
     * Merges CERTAIN-condition rules into the running GraphQL query.
     * Mutation intentionally persists to subsequent conditions (matches original behaviour).
     */
    private function mergeConditionRules(array $condition, array $graphQLQuery): array
    {
        if ($condition['applyRuleTo'] !== 'CERTAIN' || $this->isManuallyInvoked) {
            return $graphQLQuery;
        }

        $conditionsToApply = GraphQLSchemaBuilderService::buildWhereConditionFromGroup($condition['applyConditionRules']);

        if (empty($conditionsToApply)) {
            return $graphQLQuery;
        }

        if (count($graphQLQuery)) {
            $graphQLQuery['JOIN'] = $conditionsToApply;
        } else {
            $graphQLQuery = $conditionsToApply;
        }

        return $graphQLQuery;
    }

    /**
     * Processes a single action within a condition. Returns a signal constant to
     * control the outer dispatch loop, or null to continue normally.
     */
    private function processAction(array $action, array $graphQLQuery, int $jobWorkflowId, string $feedFile): ?string
    {
        $actionType = $action['actionType'];
        $actionPayload = $action['payload'];

        WorkflowLog::create([
            'job_workflow_id' => $jobWorkflowId ?: null,
            'workflow_id' => $this->workflowId,
            'record_identifier' => $this->recordIdentifier ?? null,
            'module' => $this->workflowInfo['detail']['module'],
            'status' => WorkflowLog::STATUS_IN_PROGRESS,
            'action_type' => $actionType,
        ]);

        try {
            $actionToExecute = $this->instantiateAction($actionType, $actionPayload, $jobWorkflowId);
        } catch (\RuntimeException $e) {
            // Thrown when action initialisation fails; skip the rest of this condition.
            return self::SIGNAL_SKIP_CONDITION;
        }

        if (! $actionToExecute) {
            \Log::error('WORKFLOW - Action not found: '.$actionType);

            return null;
        }

        [$placeHolderWithValues, $placeHolderToExtract] = $this->partitionPlaceholders();

        try {
            $listOfRequiredData = $actionToExecute->getListOfRequiredData();
            $listOfMandateData = $actionToExecute->getListOfMandateData();
            $listOfRequiredData = array_merge($listOfRequiredData, $placeHolderToExtract);

            if ($actionType == 'EMAIL' && strtoupper($action['payload']['emailRecipient']) != 'CUSTOM') {
                $listOfRequiredData[] = $listOfMandateData[] = ucfirst($action['payload']['emailRecipient']);
            }
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error while getting required data for action - '.$actionType.' : '.$e->getMessage());

            return null;
        }

        if ($this->isManuallyInvoked) {
            $data = [$this->data];
        } elseif (count($graphQLQuery) || count($listOfRequiredData)) {
            $result = $this->fetchDataViaGraphQL($graphQLQuery, $listOfRequiredData, $placeHolderWithValues, $actionType, $jobWorkflowId);
            if ($result === null) {
                return null;
            }
            if (! empty($result['stopAll'])) {
                return self::SIGNAL_STOP_ALL;
            }
            $data = $result['data'];
        } else {
            $data = [];
        }

        if (config('app.env') != 'production') {
            \Log::info('WORKFLOW - data: ', $data);
        }

        try {
            $data = $this->validateAndFilterData($data, $listOfMandateData, $action, $actionType, $jobWorkflowId, $placeHolderToExtract);

            if ($data === false) {
                return null;
            }

            $actionToExecute->setWorkflowData($this->workflowId, $jobWorkflowId, $this->recordIdentifier);
            $actionToExecute->setDataForAction($feedFile, $data);
            $actionToExecute->execute();
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error while executing action - '.$actionType.' : '.$e->getMessage());
        }

        return null;
    }

    /**
     * Instantiates and initialises the concrete action class for the given type.
     * Returns null for unknown action types. Throws RuntimeException if initialisation
     * fails so the caller can map it to the appropriate dispatch-loop signal.
     */
    private function instantiateAction(string $actionType, array $actionPayload, int $jobWorkflowId)
    {
        try {
            switch ($actionType) {
                case 'EMAIL':
                    $action = new EmailAction($actionType, $actionPayload);
                    $action->handle();

                    return $action;

                case 'WEB_HOOK':
                    $action = new WebhookAction($actionType, $actionPayload);
                    $action->handle();

                    return $action;

                case 'WORKFLOW_OUTPUT':
                    $action = new WorkflowOutputAction($actionType, $actionPayload);
                    $action->handle();

                    return $action;

                default:
                    \Log::error('WORKFLOW - Error while initiating action. '.$actionType);

                    return null;
            }
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog(
                $this->workflowId,
                $jobWorkflowId,
                'ERROR_INITIATING_ACTION',
                $e->getMessage()
            );
            \Log::error('WORKFLOW - Error while initiating '.$actionType.' action. '.$e->getMessage());

            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Splits $appendPlaceHolders into two arrays: keys that already have a value,
     * and keys whose value still needs to be fetched via GraphQL.
     *
     * @return array{0: array, 1: array} [$placeHolderWithValues, $placeHolderToExtract]
     */
    private function partitionPlaceholders(): array
    {
        $placeHolderWithValues = [];
        $placeHolderToExtract = [];

        foreach ($this->appendPlaceHolders as $placeHolderKey => $placeHolderValue) {
            if ($placeHolderValue) {
                $placeHolderWithValues[$placeHolderKey] = $placeHolderValue;
            } else {
                $placeHolderToExtract[] = $placeHolderKey;
            }
        }

        return [$placeHolderWithValues, $placeHolderToExtract];
    }

    /**
     * Builds the GraphQL query payload, executes the request, and parses the response
     * into a row-oriented data array ready for action execution.
     *
     * Returns null on any error (caller skips to next action).
     * Returns ['data' => [], 'stopAll' => true] when all parsed values are empty for a
     * specific record — signals the caller to stop processing all conditions.
     */
    private function fetchDataViaGraphQL(array $graphQLQuery, array $listOfRequiredData, array $placeHolderWithValues, string $actionType, int $jobWorkflowId): ?array
    {
        try {
            $moduleClassForGraphQL = $this->workflowService->getGraphQLQueryMappingService($this->workflowInfo['detail']['module'], $this->appendPlaceHolders);
            $fieldMapping = $moduleClassForGraphQL->getFieldMapping();
            $queryName = $moduleClassForGraphQL->getQueryName();
            $graphQLHeaders = $moduleClassForGraphQL->getHeaders();
            $graphQLSchemaBuilder = new GraphQLSchemaBuilderService($fieldMapping);
            foreach ($listOfRequiredData as $placeHolder) {
                $graphQLSchemaBuilder->addField($placeHolder);
            }
            $schemaData = $graphQLSchemaBuilder->getSchema();
            $graphQLRequestPayload = $graphQLSchemaBuilder->generateGraphQLQuery($schemaData, $queryName, $graphQLQuery);
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog($this->workflowId, $jobWorkflowId, 'GRAPHQL_ERROR', $e->getMessage());
            \Log::error('WORKFLOW - Error while preparing GraphQL query payload - '.$e->getMessage());

            return null;
        }

        try {
            $graphQLClient = new GraphQLClient($graphQLHeaders);
            $response = $graphQLClient->query($graphQLRequestPayload);
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog($this->workflowId, $jobWorkflowId, 'GRAPHQL_ERROR', $e->getMessage());
            \Log::error('WORKFLOW - Error while executing GraphQL query - '.$e->getMessage());

            return null;
        }

        try {
            $parsedData = $this->parseGraphQLResponse(
                $response,
                $listOfRequiredData,
                $fieldMapping,
                $moduleClassForGraphQL,
                $graphQLSchemaBuilder,
                $jobWorkflowId
            );

            $parsedData = array_merge($parsedData, $placeHolderWithValues);
            $hasAtLeastOneValue = ! empty(array_filter($parsedData, fn ($v) => $v !== null && $v !== '' && $v !== false && $v !== 'null'));

            if ($this->recordIdentifier && ! empty($parsedData) && ! $hasAtLeastOneValue) {
                \Log::warning('WORKFLOW -  Data unavailable or all required fields are empty');

                return ['data' => [], 'stopAll' => true];
            }

            if ($actionType == 'WEB_HOOK') {
                $data = $this->generatePayloadFromParsedData($parsedData);
            } else {
                $data = [$parsedData];
            }

            return ['data' => $data, 'stopAll' => false];
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog($this->workflowId, $jobWorkflowId, 'GRAPHQL_ERROR', $e->getMessage());
            \Log::error(
                'WORKFLOW - Error while extracting data from GraphQL response - '.$e->getMessage(),
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line_no' => $e->getLine(),
                ]
            );

            return null;
        }
    }

    /**
     * Iterates the required placeholder keys, extracts each value from the GraphQL
     * response using the configured jqFilter or parseResultCallback, and returns
     * a key-value map of resolved placeholder data.
     */
    private function parseGraphQLResponse(
        array $response,
        array $listOfRequiredData,
        array $fieldMapping,
        $moduleClassForGraphQL,
        GraphQLSchemaBuilderService $graphQLSchemaBuilder,
        int $jobWorkflowId
    ): array {
        $parsedData = [];

        foreach ($listOfRequiredData as $placeHolder) {
            if (! array_key_exists($placeHolder, $fieldMapping)) {
                $this->workflowService->addWorkflowLog(
                    $this->workflowId,
                    $jobWorkflowId,
                    'FIELD_MAPPING_ISSUE',
                    'Field mapping not found for placeholder: '.$placeHolder
                );
                \Log::error('WORKFLOW - Field mapping not found for placeholder: '.$placeHolder);
                $parsedData[$placeHolder] = '';

                continue;
            }

            $jqFilter = $fieldMapping[$placeHolder]['jqFilter'];
            $parseResultCallback = ! empty($fieldMapping[$placeHolder]['parseResultCallback'])
                ? $fieldMapping[$placeHolder]['parseResultCallback']
                : null;

            $placeHolderValue = '';
            if (! $jqFilter && $parseResultCallback) {
                if (method_exists($moduleClassForGraphQL, $parseResultCallback)) {
                    $placeHolderValue = $moduleClassForGraphQL->$parseResultCallback();
                }
            } else {
                $placeHolderValue = $graphQLSchemaBuilder->extractValue($response, $jqFilter);

                if ($placeHolderValue) {
                    $parsedValue = json_decode($placeHolderValue, true);
                    $placeHolderValue = json_last_error() === JSON_ERROR_NONE ? $parsedValue : $placeHolderValue;

                    if ($parseResultCallback) {
                        if (method_exists($moduleClassForGraphQL, $parseResultCallback)) {
                            $placeHolderValue = $moduleClassForGraphQL->$parseResultCallback($placeHolderValue);
                        }
                    }
                }
            }

            $parsedData[$placeHolder] = $placeHolderValue;
        }

        return $parsedData;
    }

    /**
     * Validates each data row against the mandatory-field list and, for EMAIL actions,
     * resolves and validates the recipient address. Rows that fail validation are removed.
     * Returns false when no valid rows remain (caller skips action execution).
     */
    private function validateAndFilterData(
        array $data,
        array $listOfMandateData,
        array $action,
        string $actionType,
        int $jobWorkflowId,
        array $placeHolderToExtract
    ): array|false {
        $hasPriorDataForWorkflow = false;

        foreach ($data as $index => $dataItem) {
            $data[$index]['hasPriorDataForWorkflow'] = true;

            foreach ($listOfMandateData as $mandateData) {
                if (! isset($dataItem[$mandateData]) || empty($dataItem[$mandateData])) {
                    $data[$index]['hasPriorDataForWorkflow'] = false;
                    break;
                }
            }

            if ($data[$index]['hasPriorDataForWorkflow']) {
                $hasPriorDataForWorkflow = true;
            } else {
                $this->workflowService->addWorkflowLog(
                    $this->workflowId,
                    $jobWorkflowId,
                    'MISSING_MANDATE_DATA',
                    ['data' => $data[$index], 'listOfMandateData' => $listOfMandateData]
                );
                \Log::warning('WORKFLOW - Missing mandate data', ['data' => $data[$index], 'listOfMandateData' => $listOfMandateData]);
                unset($data[$index]);

                continue;
            }

            if ($actionType == 'EMAIL') {
                $emailResult = $this->resolveEmailForAction($action, $data, $index, $placeHolderToExtract, $jobWorkflowId);
                if ($emailResult === false) {
                    $hasPriorDataForWorkflow = false;
                    unset($data[$index]);

                    continue;
                }
                $data[$index]['email'] = $emailResult;
            }
        }

        if ($hasPriorDataForWorkflow === false && count($data) == 0) {
            return false;
        }

        return $data;
    }

    /**
     * Determines the recipient email address(es) for an EMAIL action row.
     * In non-production environments delegates to filterEmailForNonProduction.
     * Returns an array of email addresses, or false when the address is blocked.
     */
    private function resolveEmailForAction(array $action, array $data, int $index, array $placeHolderToExtract, int $jobWorkflowId): array|false
    {
        if (! empty($action['payload']['emailRecipient']) && strtoupper($action['payload']['emailRecipient']) == 'CUSTOM') {
            $emailPlaceHolderValue = $action['payload']['customEmailRecipients'];
        } else {
            $emailPlaceHolder = ucfirst($action['payload']['emailRecipient']);
            $emailPlaceHolderValue = ! empty($data[$index][$emailPlaceHolder]) ? $data[$index][$emailPlaceHolder] : '';
        }

        if (! empty($placeHolderToExtract['emailRecipient'])) {
            $emailPlaceHolder = ucfirst($placeHolderToExtract['emailRecipient']);
            $emailPlaceHolderValue = ! empty($data[$index][$emailPlaceHolder]) ? $data[$index][$emailPlaceHolder] : '';
        }

        \Log::info('WORKFLOW - Actual email address: '.$emailPlaceHolderValue);

        if (! $emailPlaceHolderValue) {
            $this->workflowService->addWorkflowLog(
                $this->workflowId,
                $jobWorkflowId,
                'MISSING_EMAIL_ADDRESS',
                'System was not able to find email address for the record'
            );
        }

        if (config('app.env') != 'production') {
            return $this->filterEmailForNonProduction($emailPlaceHolderValue, $action, $jobWorkflowId);
        }

        return explode(',', $emailPlaceHolderValue);
    }

    /**
     * Applies the non-production email allowlist. Replaces the resolved address with
     * the override when configured, then checks the result against the allowed-address
     * and allowed-domain lists. Returns the filtered address array, or false when no
     * addresses pass (caller removes the row and logs UNAUTHORIZED_EMAIL_ADDRESS).
     */
    private function filterEmailForNonProduction(string $emailPlaceHolderValue, array $action, int $jobWorkflowId): array|false
    {
        $sendAllEmailsTo = config('workflow.send_all_workflow_email_to');

        if (
            $sendAllEmailsTo &&
            ! (! empty($action['payload']['emailRecipient']) &&
                strtoupper($action['payload']['emailRecipient']) == 'CUSTOM')
        ) {
            $emailPlaceHolderValue = $sendAllEmailsTo;
        }

        $emailPlaceHolderValue = explode(',', $emailPlaceHolderValue);

        $executeEmailAction = false;
        $allowedEmailAddressList1 = array_intersect($emailPlaceHolderValue, config('workflow.allowed_receiver.email'));
        if (count($allowedEmailAddressList1) > 0) {
            $executeEmailAction = true;
        }

        $allowedEmailAddressList2 = [];
        $allowedEmailShouldEndsWithInNonProduction = array_merge(['@thinktaurus.com'], config('workflow.allowed_receiver.ends_with'));
        foreach ($allowedEmailShouldEndsWithInNonProduction as $endsWith) {
            foreach ((array) $emailPlaceHolderValue as $singleEmail) {
                if (str_ends_with($singleEmail, $endsWith)) {
                    $executeEmailAction = true;
                    $allowedEmailAddressList2[] = $singleEmail;
                }
            }
        }

        $finalList = [...$allowedEmailAddressList1, ...$allowedEmailAddressList2];

        if ($executeEmailAction && count($finalList) > 0) {
            return $emailPlaceHolderValue;
        }

        $this->workflowService->addWorkflowLog(
            $this->workflowId,
            $jobWorkflowId,
            'UNAUTHORIZED_EMAIL_ADDRESS',
            'Email address not allowed in non-production env: '.implode(',', $emailPlaceHolderValue)
        );
        \Log::error('WORKFLOW - Email address not allowed in non-production env: '.implode(',', $emailPlaceHolderValue));

        return false;
    }

    /**
     * Retrieves a file from the local storage based on the provided S3 file path.
     *
     * @param  string  $s3FilePath  The S3 file path to locate the corresponding local file.
     * @return mixed Returns the local file if found, otherwise returns null or an appropriate error.
     */
    private function getFileOnLocal($s3FilePath)
    {
        $bucketName = config('workflow.aws_bucket');
        $feedFile = storage_path('app'.$s3FilePath);

        try {
            Storage::makeDirectory(dirname($s3FilePath));
            S3::downloadFile($bucketName, $s3FilePath, $feedFile);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $feedFile;
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
