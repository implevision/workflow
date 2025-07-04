<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Storage;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Services\WorkflowService;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;
use Taurus\Workflow\Services\GraphQL\Client as GraphQLClient;
use Taurus\Workflow\Services\WorkflowActions\EmailAction;

class DispatchWorkflowService
{
    private $workflowId;

    private $workflowInfo = null;

    protected $jobWorkflowRepo;

    protected $workflowService;

    protected $isWorkflowLive;

    protected $recordIdentifier;

    public function __construct(int $workflowId, int | string $recordIdentifier = 0)
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowRepo = app(JobWorkflowRepository::class);
        $this->workflowService = app(WorkflowService::class);
        $this->recordIdentifier = $recordIdentifier;
        $this->getInfo();
    }

    public function getInfo()
    {
        try {
            $workflowInfo = $this->workflowService->getWorkflowDetailsById($this->workflowId);
        } catch (\Exception $e) {
            \Log::error('Error fetching workflow details: ' . $e->getMessage());
            return false;
        }

        if (!$workflowInfo) {
            return false;
        }

        $this->workflowInfo = $workflowInfo->toArray();
    }

    public function getExecutionStrategy()
    {
        if ($this->workflowId == 1 || $this->workflowId == 2) {
            return 'batch';
        }

        return 'sequential';
    }

    public function dispatch()
    {
        if (!$this->workflowId || !is_array($this->workflowInfo)) {
            return false;
        }

        if ($this->workflowInfo['detail']['isActive'] == false) {
            \Log::info('Workflow is not active. Exiting.');
            return false;
        }

        $jobWorkflowId = 0;
        try {
            $jobWorkflow = [
                'workflow_id' => $this->workflowId,
                'status' => 'CREATED',
                'total_no_of_records_to_execute' => 0,
                'total_no_of_records_executed' => 0,
                'response' => []
            ];
            $jobWorkflowId = $this->jobWorkflowRepo->createSingle($jobWorkflow);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return false;
        }

        \Log::info(message: 'Executing workflow with ID: ' . $this->workflowId);
        \Log::info('Workflow Name: ' . $this->workflowInfo['detail']['name']);

        $allConditions = $this->workflowInfo['workFlowConditions'];

        $graphQLQuery = [];
        // NEED TO FILTER DATA IF EFFECTIVE ACTION IS 'ON_DATE_TIME' AND EVENT CONFIGURED FOR FOLLOW UP EVENT
        // Example: After/Before X day(s)/month(s)/year(s) of the event
        if (
            $this->workflowInfo['when']['effectiveActionToExecuteWorkflow'] == 'ON_DATE_TIME' &&
            !$this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['certainDateTime']
        ) {
            $graphQLQuery[] = $this->workflowService->getQueryForEffectiveAction(
                $this->workflowInfo['detail']['module'],
                $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionFrequency'],
                $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionFrequencyType'],
                $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEventIncident'],
                $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEvent']
            );
        }

        $graphQLQuery = [];
        if ($this->recordIdentifier) {
            $graphQLQuery[] = $this->workflowService->getQueryForRecordIdentifier(
                $this->workflowInfo['detail']['module'],
                $this->recordIdentifier
            );
        }

        foreach ($allConditions as $condition) {
            $feedFile = "";
            $data = [];


            if ($condition['applyRuleTo'] == 'ALL') {
                //DO NOTHING
            }

            if ($condition['applyRuleTo'] == 'CUSTOM_FEED') {
                $feedFile = $this->getFileOnLocal($condition['s3FilePath']);

                if (!$feedFile) {
                    \Log::error('Failed to download feed file from S3: ' . $condition['s3FilePath']);
                    continue; // Skip this condition if the file cannot be downloaded
                }
            }

            if ($condition['applyRuleTo'] == 'CERTAIN') {
                //GET DATA BASED ON CERTAIN CONDITION
                //APPEND IN $graphQLQuery
            }

            foreach ($condition['instanceActions'] as $action) {
                $actionToExecute = null;
                if ($action['actionType'] == 'EMAIL') {
                    $actionToExecute = new EmailAction($action['actionType'], $action['payload']);
                    $actionToExecute->handle();
                }

                $requireData = $actionToExecute->getRequiredData();

                if (count($graphQLQuery) || count($requireData)) {
                    //Build GraphQL query
                    $moduleClassForGraphQL = $this->workflowService->getGraphQLQueryMappingService($this->workflowInfo['detail']['module']);
                    $fieldMapping = $moduleClassForGraphQL->getFieldMapping();
                    $queryName = $moduleClassForGraphQL->getQueryName();
                    $graphQLSchemaBuilder = new GraphQLSchemaBuilderService($fieldMapping);
                    foreach ($requireData as $placeHolder) {
                        $graphQLSchemaBuilder->addField($placeHolder);
                    }
                    $schemaData = $graphQLSchemaBuilder->getSchema();
                    $graphQLRequestPayload = $graphQLSchemaBuilder->generateGraphQLQuery($schemaData, $queryName, $graphQLQuery);

                    //Handle GraphQL query execution                    
                    $graphQLClient = new GraphQLClient();
                    $response = $graphQLClient->query($graphQLRequestPayload);

                    $parsedData = [];
                    foreach ($requireData as $placeHolder) {
                        $jqFilter = $fieldMapping[$placeHolder]['jqFilter'];
                        $parseResultCallback = !empty($fieldMapping[$placeHolder]['parseResultCallback']) ? $fieldMapping[$placeHolder]['parseResultCallback'] : null;
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
                        $parsedData[$placeHolder] = $placeHolderValue;
                    }

                    //SET DATA FOP ACTION
                    $data[] = $parsedData;
                    \Log::info($data);
                }

                $actionToExecute->setWorkflowData($this->workflowId, $jobWorkflowId);
                $actionToExecute->setDataForAction($feedFile, $data);
                $actionToExecute->execute();
            }
        }
        return true;
    }

    private function getFileOnLocal($s3FilePath)
    {
        $bucketName = config('workflow.aws_bucket');
        $feedFile = storage_path('app' . $s3FilePath);

        try {
            Storage::makeDirectory(dirname($s3FilePath));
            S3::downloadFile($bucketName, $s3FilePath, $feedFile);
        } catch (\Exception $e) {
            \Log::error('Error downloading file from S3: ' . $e->getMessage());
            return false;
        }

        return $feedFile;
    }
}
