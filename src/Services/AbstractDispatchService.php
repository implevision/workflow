<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Storage;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Services\AWS\S3;
use Taurus\Workflow\Services\GraphQL\Client as GraphQLClient;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;
use Taurus\Workflow\Services\WorkflowActions\AbstractWorkflowAction;
use Taurus\Workflow\Services\WorkflowActions\ActionFactory;

abstract class AbstractDispatchService
{
    protected $jobWorkflowRepo;

    protected $workflowService;

    protected string $logPrefix = 'WORKFLOW';

    protected function initializeServices(): void
    {
        $this->jobWorkflowRepo = app(JobWorkflowRepository::class);
        $this->workflowService = app(WorkflowService::class);
    }

    abstract public function dispatch();

    /**
     * Create a job workflow tracking entry.
     */
    protected function createJobWorkflowEntry(?int $workflowId): int
    {
        try {
            return $this->jobWorkflowRepo->createSingle([
                'workflow_id' => $workflowId,
                'status' => 'CREATED',
                'total_no_of_records_to_execute' => 0,
                'total_no_of_records_executed' => 0,
                'response' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error("{$this->logPrefix} - Error creating job workflow entry: ".$e->getMessage());

            return 0;
        }
    }

    /**
     * Instantiate an action via the factory and call handle() to initialise it.
     *
     * @throws \InvalidArgumentException  When the action type is not registered.
     * @throws \Exception                 When handle() fails (e.g. missing template).
     */
    protected function createAndInitializeAction(string $actionType, array $actionPayload): AbstractWorkflowAction
    {
        $action = ActionFactory::create($actionType, $actionPayload);
        $action->handle();

        return $action;
    }

    /**
     * Determine which placeholder fields an action needs and which are mandatory.
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

        if ($actionType === 'EMAIL' && strtoupper($actionPayload['emailRecipient'] ?? '') !== 'CUSTOM') {
            $listOfRequiredData[] = $listOfMandateData[] = ucfirst($actionPayload['emailRecipient']);
        }

        return [$listOfRequiredData, $listOfMandateData];
    }

    /**
     * Build a GraphQL query for the given module, execute it, and parse
     * placeholder values out of the response.
     *
     * @return array Associative array of placeholder => resolved value.
     */
    protected function fetchAndParseGraphQLData(
        string $module,
        array $listOfRequiredData,
        array $graphQLQuery
    ): array {
        $moduleClassForGraphQL = $this->workflowService->getGraphQLQueryMappingService($module);
        $fieldMapping = $moduleClassForGraphQL->getFieldMapping();
        $queryName = $moduleClassForGraphQL->getQueryName();
        $graphQLHeaders = method_exists($moduleClassForGraphQL, 'getHeaders')
            ? $moduleClassForGraphQL->getHeaders()
            : [];

        $graphQLSchemaBuilder = new GraphQLSchemaBuilderService($fieldMapping);
        foreach ($listOfRequiredData as $placeHolder) {
            $graphQLSchemaBuilder->addField($placeHolder);
        }

        $schemaData = $graphQLSchemaBuilder->getSchema();
        $graphQLRequestPayload = $graphQLSchemaBuilder->generateGraphQLQuery($schemaData, $queryName, $graphQLQuery);

        $graphQLClient = new GraphQLClient($graphQLHeaders);
        $response = $graphQLClient->query($graphQLRequestPayload);

        return $this->parsePlaceholderValues($listOfRequiredData, $fieldMapping, $moduleClassForGraphQL, $graphQLSchemaBuilder, $response);
    }

    /**
     * Extract placeholder values from a raw GraphQL response.
     */
    protected function parsePlaceholderValues(
        array $listOfRequiredData,
        array $fieldMapping,
        $moduleClassForGraphQL,
        GraphQLSchemaBuilderService $graphQLSchemaBuilder,
        array $response
    ): array {
        $parsedData = [];

        foreach ($listOfRequiredData as $placeHolder) {
            if (! array_key_exists($placeHolder, $fieldMapping)) {
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

                    if ($parseResultCallback && method_exists($moduleClassForGraphQL, $parseResultCallback)) {
                        $placeHolderValue = $moduleClassForGraphQL->$parseResultCallback($placeHolderValue);
                    }
                }
            }

            $parsedData[$placeHolder] = $placeHolderValue;
        }

        return $parsedData;
    }

    /**
     * Validate mandatory data presence and resolve email addresses for each data item.
     *
     * Removes invalid items from $data in-place.
     *
     * @return bool True when at least one data item has all mandatory fields.
     */
    protected function validateAndResolveData(
        array &$data,
        array $listOfMandateData,
        string $actionType,
        array $actionPayload,
        ?int $workflowId,
        int $jobWorkflowId
    ): bool {
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
                if ($workflowId) {
                    $this->workflowService->addWorkflowLog(
                        $workflowId,
                        $jobWorkflowId,
                        'MISSING_MANDATE_DATA',
                        ['data' => $data[$index], 'listOfMandateData' => $listOfMandateData]
                    );
                }
                \Log::warning("{$this->logPrefix} - Missing mandate data", [
                    'data' => $data[$index],
                    'listOfMandateData' => $listOfMandateData,
                ]);
                unset($data[$index]);

                continue;
            }

            if ($actionType === 'EMAIL') {
                if (! $this->resolveEmailForDataItem($data, $index, $actionPayload, $workflowId, $jobWorkflowId)) {
                    $hasPriorDataForWorkflow = false;
                    unset($data[$index]);

                    continue;
                }
            }
        }

        return $hasPriorDataForWorkflow;
    }

    /**
     * Resolve the email recipient for a single data item and apply
     * non-production email filtering rules.
     *
     * @return bool False when the email address is not allowed (item should be skipped).
     */
    protected function resolveEmailForDataItem(
        array &$data,
        int $index,
        array $actionPayload,
        ?int $workflowId,
        int $jobWorkflowId
    ): bool {
        if (! empty($actionPayload['emailRecipient']) && strtoupper($actionPayload['emailRecipient']) === 'CUSTOM') {
            $emailPlaceHolderValue = $actionPayload['customEmailRecipients'];
        } else {
            $emailPlaceHolder = ucfirst($actionPayload['emailRecipient']);
            $emailPlaceHolderValue = ! empty($data[$index][$emailPlaceHolder]) ? $data[$index][$emailPlaceHolder] : '';
        }

        \Log::info("{$this->logPrefix} - Actual email address: ".$emailPlaceHolderValue);

        if (! $emailPlaceHolderValue && $workflowId) {
            $this->workflowService->addWorkflowLog(
                $workflowId,
                $jobWorkflowId,
                'MISSING_EMAIL_ADDRESS',
                'System was not able to find email address for the record'
            );
        }

        if (config('app.env') != 'production') {
            $sendAllEmailsTo = config('workflow.send_all_workflow_email_to');

            if (
                $sendAllEmailsTo &&
                strtoupper($actionPayload['emailRecipient'] ?? '') !== 'CUSTOM'
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
            $allowedEmailShouldEndsWithInNonProduction = array_merge(
                ['@thinktaurus.com'],
                config('workflow.allowed_receiver.ends_with')
            );
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
                $data[$index]['email'] = $emailPlaceHolderValue;
            } else {
                if ($workflowId) {
                    $this->workflowService->addWorkflowLog(
                        $workflowId,
                        $jobWorkflowId,
                        'UNAUTHORIZED_EMAIL_ADDRESS',
                        'Email address not allowed in non-production env: '.implode(',', $emailPlaceHolderValue)
                    );
                }
                \Log::error("{$this->logPrefix} - Email address not allowed in non-production env: ".implode(',', $emailPlaceHolderValue));

                return false;
            }
        } else {
            $data[$index]['email'] = explode(',', $emailPlaceHolderValue);
        }

        return true;
    }

    /**
     * Fan-out a flat parsed-data map into per-record payloads
     * (used by webhook actions when values are arrays).
     */
    protected function generatePayloadFromParsedData(array $parsedData): array
    {
        $totalPayloadToGenerate = 0;

        foreach ($parsedData as $key => $value) {
            if (is_array($value)) {
                $totalPayloadToGenerate = max($totalPayloadToGenerate, count($value));
            }
        }

        if (! $totalPayloadToGenerate) {
            return $parsedData;
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

    /**
     * Download a file from S3 to local storage.
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
