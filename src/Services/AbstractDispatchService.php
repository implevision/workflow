<?php

namespace Taurus\Workflow\Services;

use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Services\GraphQL\Client as GraphQLClient;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;
use Taurus\Workflow\Services\WorkflowActions\AbstractWorkflowAction;
use Taurus\Workflow\Services\WorkflowActions\EmailAction;
use Taurus\Workflow\Services\WorkflowActions\WebhookAction;
use Taurus\Workflow\Services\WorkflowActions\WorkflowOutputAction;

/**
 * Shared execution pipeline for the two workflow dispatch engines
 * (DispatchWorkflowService and DispatchManualWorkflowService): action
 * instantiation, mandate-data validation, and email-recipient resolution.
 */
abstract class AbstractDispatchService
{
    protected $jobWorkflowRepo;

    protected $workflowService;

    protected string $logPrefix;

    abstract public function dispatch();

    protected function initializeServices(): void
    {
        $this->jobWorkflowRepo = app(JobWorkflowRepository::class);
        $this->workflowService = app(WorkflowService::class);
    }

    /**
     * Creates a job-workflow tracking record in the database.
     * Returns 0 (falsy) on failure so the caller can short-circuit; logs the error.
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

            return $this->jobWorkflowRepo->createSingle($jobWorkflow);
        } catch (\Exception $e) {
            \Log::error("{$this->logPrefix} - Error while creating entry in JOB WORKFLOW table. ".$e->getMessage());

            return 0;
        }
    }

    /**
     * Determines which placeholder fields an action needs and which are mandatory.
     * For EMAIL actions, also requires (and mandates) the recipient placeholder unless
     * the recipient is CUSTOM. $extraPlaceholders (Dispatch's appendPlaceHolders-derived
     * placeHolderToExtract) is merged into the required list only — Manual has no such
     * concept and omits it.
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
     * Instantiates and initialises the concrete action class for the given type,
     * wiring in the module's extended template info before handle() is called.
     * Returns null for unsupported/unknown action types. Throws RuntimeException
     * if initialisation fails so the caller can map it to the appropriate
     * dispatch-loop signal (Dispatch: skip the rest of the condition;
     * Manual: skip to the next selected action).
     */
    protected function instantiateAction(
        string $actionType,
        array $actionPayload,
        string $module,
        int $workflowId,
        int $jobWorkflowId,
        array $supportedActionTypes = ['EMAIL', 'WEB_HOOK', 'WORKFLOW_OUTPUT']
    ): ?AbstractWorkflowAction {
        if (! in_array($actionType, $supportedActionTypes, true)) {
            \Log::error("{$this->logPrefix} - Error while initiating action. ".$actionType);

            return null;
        }

        $extendedTemplateInfoForModule = in_array($actionType, ['EMAIL', 'WORKFLOW_OUTPUT'], true)
            ? $this->workflowService->getExtendedTemplateInfoForModule($module, $actionPayload)
            : [];

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
                    \Log::error("{$this->logPrefix} - Error while initiating action. ".$actionType);

                    return null;
            }
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog(
                $workflowId,
                $jobWorkflowId,
                'ERROR_INITIATING_ACTION',
                $e->getMessage()
            );
            \Log::error("{$this->logPrefix} - Error while initiating ".$actionType.' action. '.$e->getMessage());

            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Validates each data row against the mandatory-field list and, for EMAIL actions,
     * resolves and validates the recipient address. Rows that fail validation are removed.
     * Returns false when no valid rows remain (caller skips action execution).
     */
    protected function validateAndFilterData(
        array $data,
        array $listOfMandateData,
        array $action,
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

            if ($data[$index]['hasPriorDataForWorkflow']) {
                $hasPriorDataForWorkflow = true;
            } else {
                $this->workflowService->addWorkflowLog(
                    $workflowId,
                    $jobWorkflowId,
                    'MISSING_MANDATE_DATA',
                    ['missingMandateDataRecords' => $missingMandateDataRecords, 'data' => $data[$index], 'listOfMandateData' => $listOfMandateData]
                );
                \Log::warning("{$this->logPrefix} - Missing mandate data", ['missingMandateDataRecords' => $missingMandateDataRecords, 'data' => $data[$index], 'listOfMandateData' => $listOfMandateData]);
                unset($data[$index]);

                continue;
            }

            if ($actionType == 'EMAIL') {
                $emailResult = $this->resolveEmailForAction($action, $data, $index, $placeHolderToExtract, $workflowId, $jobWorkflowId);
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
    protected function resolveEmailForAction(array $action, array $data, int $index, array $placeHolderToExtract, int $workflowId, int $jobWorkflowId): array|false
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

        \Log::info("{$this->logPrefix} - Actual email address: ".$emailPlaceHolderValue);

        if (! $emailPlaceHolderValue) {
            $this->workflowService->addWorkflowLog(
                $workflowId,
                $jobWorkflowId,
                'MISSING_EMAIL_ADDRESS',
                'System was not able to find email address for the record'
            );
        }

        if (config('app.env') != 'production') {
            return $this->filterEmailForNonProduction($emailPlaceHolderValue, $action, $workflowId, $jobWorkflowId);
        }

        return explode(',', $emailPlaceHolderValue);
    }

    /**
     * Applies the non-production email allowlist. Replaces the resolved address with
     * the override when configured, then checks the result against the allowed-address
     * and allowed-domain lists. Returns the filtered address array, or false when no
     * addresses pass (caller removes the row and logs UNAUTHORIZED_EMAIL_ADDRESS).
     */
    protected function filterEmailForNonProduction(string $emailPlaceHolderValue, array $action, int $workflowId, int $jobWorkflowId): array|false
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
            $workflowId,
            $jobWorkflowId,
            'UNAUTHORIZED_EMAIL_ADDRESS',
            'Email address not allowed in non-production env: '.implode(',', $emailPlaceHolderValue)
        );
        \Log::error("{$this->logPrefix} - Email address not allowed in non-production env: ".implode(',', $emailPlaceHolderValue));

        return false;
    }

    /**
     * Builds the GraphQL request payload for a module and executes it.
     *
     * $configureQuery, when given, is invoked with the resolved module GraphQL-mapping
     * instance right after it is created — this is Dispatch's hook for pagination
     * (setPage/setQueryArgsContext) before getQueryArgs()/generateGraphQLQuery() run.
     * Manual has no such concept and omits it, matching its original (unpaginated) query.
     *
     * Returns null on failure (both the query-build and query-execute steps are logged
     * separately with GRAPHQL_ERROR), or an array with:
     *   moduleClassForGraphQL, fieldMapping, graphQLSchemaBuilder, queryArgs, response
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
            if ($configureQuery !== null) {
                $configureQuery($moduleClassForGraphQL);
                $queryArgs = $moduleClassForGraphQL->getQueryArgs();
            }

            $graphQLRequestPayload = $graphQLSchemaBuilder->generateGraphQLQuery($schemaData, $queryName, $graphQLQuery, $queryArgs);
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog($workflowId, $jobWorkflowId, 'GRAPHQL_ERROR', $e->getMessage());
            \Log::error("{$this->logPrefix} - Error while preparing GraphQL query payload - ".$e->getMessage());

            return null;
        }

        try {
            $graphQLClient = new GraphQLClient($graphQLHeaders);
            $response = $graphQLClient->query($graphQLRequestPayload);
        } catch (\Exception $e) {
            $this->workflowService->addWorkflowLog($workflowId, $jobWorkflowId, 'GRAPHQL_ERROR', $e->getMessage());
            \Log::error("{$this->logPrefix} - Error while executing GraphQL query - ".$e->getMessage());

            return null;
        }

        return [
            'moduleClassForGraphQL' => $moduleClassForGraphQL,
            'fieldMapping' => $fieldMapping,
            'graphQLSchemaBuilder' => $graphQLSchemaBuilder,
            'queryArgs' => $queryArgs,
            'response' => $response,
        ];
    }

    /**
     * Iterates the required placeholder keys, extracts each value from the GraphQL
     * response using the configured jqFilter or parseResultCallback, and returns
     * a key-value map of resolved placeholder data.
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
                \Log::error("{$this->logPrefix} - Field mapping not found for placeholder: ".$placeHolder);
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
}
