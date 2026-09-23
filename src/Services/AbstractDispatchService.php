<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Taurus\Workflow\Models\WorkflowLog;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Services\AWS\S3;
use Taurus\Workflow\Services\GraphQL\Client as GraphQLClient;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;
use Taurus\Workflow\Services\WorkflowActions\AbstractWorkflowAction;
use Taurus\Workflow\Services\WorkflowActions\EmailAction;
use Taurus\Workflow\Services\WorkflowActions\WebhookAction;
use Taurus\Workflow\Services\WorkflowActions\WorkflowOutputAction;

/**
 * Class AbstractDispatchService
 *
 * Shared execution pipeline for the workflow dispatch engines
 * (DispatchWorkflowService and DispatchManualWorkflowService).
 *
 * Holds the parts of the dispatch pipeline that are identical in both engines:
 * job-workflow and workflow-log row creation, action instantiation, placeholder
 * requirement resolution, GraphQL query building/execution/parsing, mandate-data
 * validation and email-recipient resolution.
 *
 * Anything that is specific to one engine - the workflow configuration loading,
 * condition looping, pagination and custom record extraction (DispatchWorkflowService),
 * or the caller-supplied action configs (DispatchManualWorkflowService) - stays in
 * the concrete class.
 *
 * @property JobWorkflowRepository $jobWorkflowRepo Repository used to create the job-workflow tracking rows.
 * @property WorkflowService $workflowService Provides the module GraphQL mappings, template info, query builders and error logging.
 * @property int|string $recordIdentifier Identifier of the record the run targets; 0 when not scoped to one record.
 * @property int|string|null $userId The user who triggered the run, or null when triggered by the system.
 * @property string $logPrefix Prefix on every log line, so the two engines stay distinguishable.
 */
abstract class AbstractDispatchService
{
    /**
     * All action types the dispatch engines know how to instantiate.
     */
    protected const SUPPORTED_ACTION_TYPES = ['EMAIL', 'WEB_HOOK', 'WORKFLOW_OUTPUT'];

    protected $jobWorkflowRepo;

    protected $workflowService;

    protected int|string $recordIdentifier = 0;

    protected int|string|null $userId = null;

    protected string $logPrefix = 'WORKFLOW';

    /**
     * Runs the engine. Return type differs per engine, so it is left unconstrained here.
     */
    abstract public function dispatch();

    /**
     * Resolves the container services shared by both engines.
     */
    protected function initializeServices(): void
    {
        $this->jobWorkflowRepo = app(JobWorkflowRepository::class);
        $this->workflowService = app(WorkflowService::class);
    }

    /**
     * Creates a job-workflow tracking record in the database and registers its ID
     * globally, so the rest of the run can attribute its work to this job.
     *
     * Returns 0 (falsy) on failure so the caller can short-circuit; the error is logged.
     *
     * @param  int|null  $workflowId  The saved workflow id, or null for a manual run.
     * @param  string|null  $referenceId  Optional external reference for the job.
     */
    protected function createJobWorkflowEntry(?int $workflowId, ?string $referenceId = null): int
    {
        try {
            $jobWorkflow = [
                'workflow_id' => $workflowId,
                'status' => 'CREATED',
                'total_no_of_records_to_execute' => 0,
                'total_no_of_records_executed' => 0,
                'response' => [],
                'reference_id' => $referenceId,
            ];

            $jobWorkflowId = $this->jobWorkflowRepo->createSingle($jobWorkflow);
            setRunningJobWorkflowId($jobWorkflowId);

            return $jobWorkflowId;
        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} - Error while creating entry in JOB WORKFLOW table. ".$e->getMessage());

            return 0;
        }
    }

    /**
     * Creates the IN_PROGRESS workflow-log row for an action that is about to run.
     *
     * The record identifier and the triggering user are read from the engine, since
     * both are set up front and are the same for every action in the run. user_id is
     * nullable, so a system-triggered run simply stores null.
     *
     * $actionConfigPayload is only supplied by the manual engine, which records the
     * exact action config it was handed.
     */
    protected function createInProgressLog(
        int $workflowId,
        int $jobWorkflowId,
        string $module,
        string $actionType,
        ?array $actionConfigPayload = null
    ): WorkflowLog {
        $attributes = [
            'job_workflow_id' => $jobWorkflowId ?: null,
            'workflow_id' => $workflowId,
            'record_identifier' => $this->recordIdentifier ?? null,
            'module' => $module,
            'status' => WorkflowLog::STATUS_IN_PROGRESS,
            'action_type' => $actionType,
            'user_id' => $this->userId,
        ];

        if ($actionConfigPayload !== null) {
            $attributes['action_config_payload'] = $actionConfigPayload;
        }

        return WorkflowLog::create($attributes);
    }

    /**
     * Instantiates and initialises the concrete action class for the given type,
     * wiring in the module's extended template info before handle() is called.
     *
     * Returns null for action types outside $supportedActionTypes. Throws
     * RuntimeException when initialisation fails, so the caller can map it to the
     * appropriate loop signal (DispatchWorkflowService skips the rest of the
     * condition; DispatchManualWorkflowService skips to the next selected action).
     *
     * @param  array  $supportedActionTypes  Action types this engine accepts.
     *
     * @throws \RuntimeException When the action class fails to initialise.
     */
    protected function instantiateAction(
        string $actionType,
        array $actionPayload,
        string $module,
        int $workflowId,
        int $jobWorkflowId,
        array $supportedActionTypes = self::SUPPORTED_ACTION_TYPES
    ): ?AbstractWorkflowAction {
        if (! in_array($actionType, $supportedActionTypes, true)) {
            Log::error("{$this->logPrefix} - Error while initiating action. ".$actionType);

            return null;
        }

        $extendedTemplateInfoForModule = $this->workflowService->getExtendedTemplateInfoForModule(
            $module,
            $actionPayload
        );

        try {
            switch ($actionType) {
                case 'EMAIL':
                    $action = new EmailAction($actionType, $actionPayload);
                    $action->setExtendedTemplateInfo($extendedTemplateInfoForModule);
                    $action->handle();

                    return $action;

                case 'WEB_HOOK':
                    $action = new WebhookAction($actionType, $actionPayload);
                    $action->handle();

                    return $action;

                case 'WORKFLOW_OUTPUT':
                    $action = new WorkflowOutputAction($actionType, $actionPayload);
                    $action->setExtendedTemplateInfo($extendedTemplateInfoForModule);
                    $action->handle();

                    return $action;

                default:
                    Log::error("{$this->logPrefix} - Error while initiating action. ".$actionType);

                    return null;
            }
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog(
                $workflowId,
                $jobWorkflowId,
                'ERROR_INITIATING_ACTION',
                $e->getMessage()
            );
            Log::error("{$this->logPrefix} - Error while initiating ".$this->actionLabel($actionType).' action. '.$e->getMessage());

            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Human readable label for an action type
     */
    private function actionLabel(string $actionType): string
    {
        return match ($actionType) {
            'EMAIL' => 'email',
            'WEB_HOOK' => 'webhook',
            'WORKFLOW_OUTPUT' => 'workflow output',
            default => $actionType,
        };
    }

    /**
     * Splits appendPlaceHolders into the placeholders that already carry a value and
     * the placeholder keys whose values still need to be fetched from GraphQL.
     *
     * @return array{0: array, 1: array} [$placeHolderWithValues, $placeHolderToExtract]
     */
    protected function partitionPlaceholders(array $appendPlaceHolders): array
    {
        $placeHolderWithValues = [];
        $placeHolderToExtract = [];

        foreach ($appendPlaceHolders as $placeHolderKey => $placeHolderValue) {
            if ($placeHolderValue) { // NO NEED TO EXTRACT IF VALUE IS ALREADY AVAILABLE
                $placeHolderWithValues[$placeHolderKey] = $placeHolderValue;

                continue;
            }

            $placeHolderToExtract[] = $placeHolderKey;
        }

        return [$placeHolderWithValues, $placeHolderToExtract];
    }

    /**
     * Determines which placeholder fields an action needs and which are mandatory.
     *
     * For EMAIL actions the recipient placeholder is also required (and mandated)
     * unless the recipient is CUSTOM. $extraPlaceholders is merged into the required
     * list only - it carries DispatchWorkflowService's placeHolderToExtract, which the
     * manual engine has no equivalent of.
     *
     * @return array{0: array, 1: array} [$listOfRequiredData, $listOfMandateData]
     */
    protected function resolveActionDataRequirements(
        AbstractWorkflowAction $action,
        string $actionType,
        array $actionPayload,
        array $extraPlaceholders = []
    ): array {
        $listOfRequiredData = $action->getListOfRequiredData();
        $listOfMandateData = $action->getListOfMandateData();

        $listOfRequiredData = array_merge($listOfRequiredData, $extraPlaceholders);

        if ($actionType == 'EMAIL' && strtoupper($actionPayload['emailRecipient']) != 'CUSTOM') {
            $listOfRequiredData[] = $listOfMandateData[] = ucfirst($actionPayload['emailRecipient']);
        }

        return [$listOfRequiredData, $listOfMandateData];
    }

    /**
     * Builds the GraphQL request payload for a module and executes it.
     *
     * $configureQuery, when given, is invoked with the resolved module GraphQL-mapping
     * instance right after it is created - this is DispatchWorkflowService's hook for
     * pagination (setPage/setQueryArgsContext) before getQueryArgs() and the query
     * generation run. The manual engine has no such concept and omits it, matching its
     * original unpaginated query.
     *
     * Returns null on failure (the query-build and query-execute steps are logged
     * separately with GRAPHQL_ERROR), or an array with:
     * moduleClassForGraphQL, fieldMapping, graphQLSchemaBuilder, queryArgs, response
     */
    protected function buildAndExecuteGraphQLQuery(
        string $module,
        array $appendPlaceHolders,
        array $listOfRequiredData,
        array $graphQLQuery,
        bool $useHeaders,
        int $workflowId,
        int $jobWorkflowId,
        ?\Closure $configureQuery = null
    ): ?array {
        try {
            $moduleClassForGraphQL = $this->workflowService->getGraphQLQueryMappingService($module, $appendPlaceHolders);
            $fieldMapping = $moduleClassForGraphQL->getFieldMapping();
            $queryName = $moduleClassForGraphQL->getQueryName();
            $graphQLHeaders = $useHeaders ? $moduleClassForGraphQL->getHeaders() : [];

            $graphQLSchemaBuilder = new GraphQLSchemaBuilderService($fieldMapping);
            foreach ($listOfRequiredData as $placeHolder) {
                $graphQLSchemaBuilder->addField($placeHolder);
            }
            $schemaData = $graphQLSchemaBuilder->getSchema();

            $queryArgs = [];
            $page = 0;
            if ($configureQuery !== null) {
                $page = $configureQuery($moduleClassForGraphQL) ?? 0;
                $queryArgs = $moduleClassForGraphQL->getQueryArgs();
            }

            $graphQLRequestPayload = $graphQLSchemaBuilder->generateGraphQLQuery(
                $schemaData,
                $queryName,
                $graphQLQuery,
                $queryArgs,
                $page,
                $moduleClassForGraphQL->supportsPagination()
            );
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog($workflowId, $jobWorkflowId, 'GRAPHQL_ERROR', $e->getMessage());
            Log::error("{$this->logPrefix} - Error while preparing GraphQL query payload - ".$e->getMessage());

            return null;
        }

        try {
            Log::info("{$this->logPrefix} - GraphQL Request Payload: ".$graphQLRequestPayload);

            $graphQLClient = new GraphQLClient($graphQLHeaders);
            $response = $graphQLClient->query($graphQLRequestPayload);

            Log::info("{$this->logPrefix} - GraphQL Response: ", $response);
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog($workflowId, $jobWorkflowId, 'GRAPHQL_ERROR', $e->getMessage());
            Log::error("{$this->logPrefix} - Error while executing GraphQL query - ".$e->getMessage());

            return null;
        }

        return [
            'moduleClassForGraphQL' => $moduleClassForGraphQL,
            'fieldMapping' => $fieldMapping,
            'graphQLSchemaBuilder' => $graphQLSchemaBuilder,
            'queryName' => $queryName,
            'queryArgs' => $queryArgs,
            'response' => $response,
        ];
    }

    /**
     * Iterates the required placeholder keys, extracts each value from the GraphQL
     * response using the configured jqFilter or parseResultCallback, and returns a
     * key-value map of resolved placeholder data.
     *
     * Placeholders with no field mapping resolve to an empty string and are reported
     * via a FIELD_MAPPING_ISSUE workflow log.
     */
    protected function parseGraphQLResponse(
        array $response,
        array $listOfRequiredData,
        array $fieldMapping,
        $moduleClassForGraphQL,
        GraphQLSchemaBuilderService $graphQLSchemaBuilder,
        int $workflowId,
        int $jobWorkflowId
    ): array {
        $parsedData = [];

        foreach ($listOfRequiredData as $placeHolder) {
            if (! array_key_exists($placeHolder, $fieldMapping)) {
                $this->workflowService->addWorkflowLog(
                    $workflowId,
                    $jobWorkflowId,
                    'FIELD_MAPPING_ISSUE',
                    'Field mapping not found for placeholder: '.$placeHolder
                );
                Log::error("{$this->logPrefix} - Field mapping not found for placeholder: ".$placeHolder);
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
     * resolves and validates the recipient address. Rows that fail validation are
     * removed and reported via a workflow log.
     *
     * Returns the filtered rows, or false when no valid row remains so the caller can
     * skip executing the action.
     */
    protected function validateAndFilterData(
        array $data,
        array $listOfMandateData,
        array $actionPayload,
        string $actionType,
        int $workflowId,
        int $jobWorkflowId,
        array $placeHolderToExtract = []
    ): array|false {
        $hasPriorDataForWorkflow = false;

        foreach ($data as $index => $dataItem) {
            $data[$index]['hasPriorDataForWorkflow'] = true;
            $missingMandateDataRecords = [];

            foreach ($listOfMandateData as $mandateData) {
                if (! isset($dataItem[$mandateData]) || empty($dataItem[$mandateData])) {
                    $data[$index]['hasPriorDataForWorkflow'] = false;
                    $missingMandateDataRecords[] = $mandateData;
                }
            }

            // FROM BUNCH OF RECORDS THERE MUST BE RECORD WHICH HAS MANDATE DATA
            if ($data[$index]['hasPriorDataForWorkflow']) {
                $hasPriorDataForWorkflow = true;
            } else {
                $logContext = [
                    'missingMandateDataRecords' => $missingMandateDataRecords,
                    'data' => $data[$index],
                    'listOfMandateData' => $listOfMandateData,
                ];
                $this->workflowService->addWorkflowLog(
                    $workflowId,
                    $jobWorkflowId,
                    'MISSING_MANDATE_DATA',
                    $logContext
                );
                Log::warning("{$this->logPrefix} - Missing mandate data", $logContext);
                unset($data[$index]);

                continue;
            }

            if ($actionType == 'EMAIL') {
                $emailAddresses = $this->resolveEmailForAction(
                    $actionPayload,
                    $data,
                    $index,
                    $placeHolderToExtract,
                    $workflowId,
                    $jobWorkflowId
                );

                if ($emailAddresses === false) {
                    $hasPriorDataForWorkflow = false;
                    unset($data[$index]);

                    continue;
                }

                $data[$index]['email'] = $emailAddresses;
            }
        }

        if ($hasPriorDataForWorkflow === false && count($data) == 0) {
            return false;
        }

        return $data;
    }

    /**
     * Determines the recipient email address(es) for an EMAIL action row.
     *
     * In non-production environments the result is passed through the allowlist filter.
     * Returns an array of email addresses, or false when the address is blocked.
     */
    protected function resolveEmailForAction(
        array $actionPayload,
        array $data,
        int $index,
        array $placeHolderToExtract,
        int $workflowId,
        int $jobWorkflowId
    ): array|false {
        if (! empty($actionPayload['emailRecipient']) && strtoupper($actionPayload['emailRecipient']) == 'CUSTOM') {
            $emailPlaceHolderValue = $actionPayload['customEmailRecipients'];
        } else {
            $emailPlaceHolder = ucfirst($actionPayload['emailRecipient']);
            $emailPlaceHolderValue = ! empty($data[$index][$emailPlaceHolder]) ? $data[$index][$emailPlaceHolder] : '';
        }

        if (! empty($placeHolderToExtract['emailRecipient'])) {
            $emailPlaceHolder = ucfirst($placeHolderToExtract['emailRecipient']);
            $emailPlaceHolderValue = ! empty($data[$index][$emailPlaceHolder]) ? $data[$index][$emailPlaceHolder] : '';
        }

        Log::info("{$this->logPrefix} - Actual email address: ".$emailPlaceHolderValue);

        if (! $emailPlaceHolderValue) {
            $this->workflowService->addWorkflowLog(
                $workflowId,
                $jobWorkflowId,
                'MISSING_EMAIL_ADDRESS',
                'System was not able to find email address for the record'
            );
        }

        if (config('app.env') != 'production') {
            return $this->filterEmailForNonProduction($emailPlaceHolderValue, $actionPayload, $workflowId, $jobWorkflowId);
        }

        return explode(',', $emailPlaceHolderValue);
    }

    /**
     * Applies the non-production email allowlist. Replaces the resolved address with the
     * configured override (unless the recipient is CUSTOM), then checks the result against
     * the allowed-address and allowed-domain lists.
     *
     * Returns the filtered address array, or false when no address passes - in which case
     * UNAUTHORIZED_EMAIL_ADDRESS is logged and the caller drops the row.
     */
    protected function filterEmailForNonProduction(
        string $emailPlaceHolderValue,
        array $actionPayload,
        int $workflowId,
        int $jobWorkflowId
    ): array|false {
        $sendAllEmailsTo = config('workflow.send_all_workflow_email_to');

        if (
            $sendAllEmailsTo &&
            ! (! empty($actionPayload['emailRecipient']) &&
                strtoupper($actionPayload['emailRecipient']) == 'CUSTOM')
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

        $implodedEmailList = implode(',', $emailPlaceHolderValue);
        $this->workflowService->addWorkflowLog(
            $workflowId,
            $jobWorkflowId,
            'UNAUTHORIZED_EMAIL_ADDRESS',
            'Email address not allowed in non-production env: '.$implodedEmailList
        );
        Log::error("{$this->logPrefix} - Email address not allowed in non-production env: ".$implodedEmailList);

        return false;
    }

    /**
     * Downloads a feed file from S3 into local storage and returns its local path.
     *
     * @param  string  $s3FilePath  The S3 file path to download.
     *
     * @throws \Exception When the download fails.
     */
    protected function getFileOnLocal(string $s3FilePath): string
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
}
