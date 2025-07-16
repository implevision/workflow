<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Storage;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Services\WorkflowService;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;
use Taurus\Workflow\Services\GraphQL\Client as GraphQLClient;
use Taurus\Workflow\Services\WorkflowActions\EmailAction;

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
    private $workflowId;

    private $workflowInfo = null;

    protected $jobWorkflowRepo;

    protected $workflowService;

    protected $isWorkflowLive;

    protected $recordIdentifier;

    /**
     * DispatchWorkflowService constructor.
     *
     * @param int $workflowId The ID of the workflow to be dispatched.
     * @param int|string $recordIdentifier An optional identifier for the record, default is 0.
     */
    public function __construct(int $workflowId, int | string $recordIdentifier = 0)
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowRepo = app(JobWorkflowRepository::class);
        $this->workflowService = app(WorkflowService::class);
        $this->recordIdentifier = $recordIdentifier;
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
            \Log::error('WORKFLOW - Error fetching workflow details: ' . $e->getMessage());
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
        if (!$this->workflowId || !is_array($this->workflowInfo)) {
            return false;
        }

        if ($this->workflowInfo['detail']['isActive'] == false) {
            \Log::info('WORKFLOW - Workflow is not active. Exiting.');
            return false;
        }

        \Log::info('WORKFLOW - Name: ' . $this->workflowInfo['detail']['name']);

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
            setRunningJobWorkflowId($jobWorkflowId);
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error while creating entry in JOB WORKFLOW table. ' . $e->getMessage());
            return false;
        }

        setModuleForCurrentWorkflow($this->workflowInfo['detail']['module']);
        $allConditions = $this->workflowInfo['workFlowConditions'];

        $graphQLQuery = [];
        // NEED TO FILTER DATA IF EFFECTIVE ACTION IS 'ON_DATE_TIME' AND EVENT CONFIGURED FOR FOLLOW UP EVENT
        // Example: After/Before X day(s)/month(s)/year(s) of the event
        if (
            $this->workflowInfo['when']['effectiveActionToExecuteWorkflow'] == 'ON_DATE_TIME' &&
            !$this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['certainDateTime']
        ) {
            try {
                $graphQLQuery[] = $this->workflowService->getQueryForEffectiveAction(
                    $this->workflowInfo['detail']['module'],
                    $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionFrequency'],
                    $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionFrequencyType'],
                    $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEventIncident'],
                    $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow']['executionEvent']
                );
            } catch (\Exception $e) {
                throw new \Exception('Error while creating GraphQL query for effective action. ' . $e->getMessage());
            }
        }

        $graphQLQuery = [];
        if ($this->recordIdentifier) {
            try {
                $graphQLQuery[] = $this->workflowService->getQueryForRecordIdentifier(
                    $this->workflowInfo['detail']['module'],
                    $this->recordIdentifier
                );
            } catch (\Exception $e) {
                throw new \Exception('Error while creating GraphQL query for record identifier. ' . $e->getMessage());
            }
        }

        foreach ($allConditions as $condition) {
            $feedFile = "";
            $data = [];


            if ($condition['applyRuleTo'] == 'ALL') {
                //DO NOTHING
            }

            if ($condition['applyRuleTo'] == 'CUSTOM_FEED') {
                try {
                    $feedFile = $this->getFileOnLocal($condition['s3FilePath']);
                } catch (\Exception $e) {
                    \Log::error('WORKFLOW - Failed to download feed file from S3: ' . $condition['s3FilePath']);
                    \Log::error('WORKFLOW - ' . $e->getMessage());
                }
            }

            if ($condition['applyRuleTo'] == 'CERTAIN') {
                //GET DATA BASED ON CERTAIN CONDITION
                //APPEND IN $graphQLQuery               
            }

            foreach ($condition['instanceActions'] as $action) {
                $actionToExecute = null;
                if ($action['actionType'] == 'EMAIL') {
                    try {
                        $actionToExecute = new EmailAction($action['actionType'], $action['payload']);
                        $actionToExecute->handle();
                    } catch (\Exception $e) {
                        \Log::error('WORKFLOW - Error while initiating email action. ' . $e->getMessage());
                        continue;
                    }
                }

                if (!$actionToExecute) {
                    \Log::error('WORKFLOW - Action not found: ' . $action['actionType']);
                    continue;
                }

                try {
                    $requireData = $actionToExecute ? $actionToExecute->getRequiredData() : [];
                } catch (\Exception $e) {
                    \Log::error('WORKFLOW - Error while getting required data for action - ' . $action['actionType'] . " : " . $e->getMessage());
                    continue;
                }

                if (count($graphQLQuery) || count($requireData)) {
                    //Build GraphQL query
                    try {
                        $moduleClassForGraphQL = $this->workflowService->getGraphQLQueryMappingService($this->workflowInfo['detail']['module']);
                        $fieldMapping = $moduleClassForGraphQL->getFieldMapping();
                        $queryName = $moduleClassForGraphQL->getQueryName();
                        $graphQLSchemaBuilder = new GraphQLSchemaBuilderService($fieldMapping);
                        foreach ($requireData as $placeHolder) {
                            $graphQLSchemaBuilder->addField($placeHolder);
                        }
                        $schemaData = $graphQLSchemaBuilder->getSchema();
                        $graphQLRequestPayload = $graphQLSchemaBuilder->generateGraphQLQuery($schemaData, $queryName, $graphQLQuery);
                    } catch (\Exception $e) {
                        \Log::error('WORKFLOW - Error while preparing GraphQL query payload - ' . $e->getMessage());
                        continue;
                    }

                    //Handle GraphQL query execution
                    try {
                        $graphQLClient = new GraphQLClient();
                        $response = $graphQLClient->query($graphQLRequestPayload);
                    } catch (\Exception $e) {
                        \Log::error('WORKFLOW - Error while executing GraphQL query - ' . $e->getMessage());
                        continue;
                    }

                    $parsedData = [];


                    try {
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
                    } catch (\Exception $e) {
                        \Log::error('WORKFLOW - Error while extracting data from GraphQL response - ' . $e->getMessage());
                        continue;
                    }

                    //VALIDATE ALL REQUIRED INFO IS PRESENT OR NOT
                }

                try {
                    $actionToExecute->setWorkflowData($this->workflowId, $jobWorkflowId, $this->recordIdentifier);
                    $actionToExecute->setDataForAction($feedFile, $data);
                    $actionToExecute->execute();
                } catch (\Exception $e) {
                    \Log::error('WORKFLOW - Error while executing action - ' . $action['actionType'] . " : " . $e->getMessage());
                    continue;
                }
            }
        }
        return true;
    }

    /**
     * Retrieves a file from the local storage based on the provided S3 file path.
     *
     * @param string $s3FilePath The S3 file path to locate the corresponding local file.
     * @return mixed Returns the local file if found, otherwise returns null or an appropriate error.
     */
    private function getFileOnLocal($s3FilePath)
    {
        $bucketName = config('workflow.aws_bucket');
        $feedFile = storage_path('app' . $s3FilePath);

        try {
            Storage::makeDirectory(dirname($s3FilePath));
            S3::downloadFile($bucketName, $s3FilePath, $feedFile);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $feedFile;
    }
}
