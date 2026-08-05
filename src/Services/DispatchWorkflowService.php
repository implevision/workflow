<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Storage;
use Taurus\Workflow\Models\WorkflowLog;
use Taurus\Workflow\Services\AWS\S3;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

/**
 * Class DispatchWorkflowService
 *
 * This class is responsible for managing the dispatch workflow.
 * It handles the workflow ID, workflow information, and interacts
 * with the job workflow repository and workflow service.
 *
 * @property int $workflowId The ID of the workflow.
 * @property mixed|null $workflowInfo Information related to the workflow.
 * @property \Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository $jobWorkflowRepo Repository for job workflows.
 * @property WorkflowService $workflowService Service for managing workflows.
 * @property bool $isWorkflowLive Indicates if the workflow is currently live.
 * @property string $recordIdentifier Identifier for the record associated with the workflow.
 */
class DispatchWorkflowService extends AbstractDispatchService
{
    private $workflowId;

    private $workflowInfo = null;

    protected $isWorkflowLive;

    protected $recordIdentifier;

    protected $data;

    protected $appendPlaceHolders;

    protected $page;

    protected $isManuallyInvoked = false;

    protected $referenceId;

    protected string $logPrefix = 'WORKFLOW';

    /**
     * DispatchWorkflowService constructor.
     *
     * @param  int  $workflowId  The ID of the workflow to be dispatched.
     * @param  int|string  $recordIdentifier  An optional identifier for the record, default is 0.
     */
    public function __construct(int $workflowId, int|string $recordIdentifier = 0, $data = [], $appendPlaceHolders = [], ?string $referenceId = null, int $page = 0)
    {
        $this->workflowId = $workflowId;
        $this->initializeServices();
        $this->recordIdentifier = $recordIdentifier;
        $this->data = $data;
        $this->appendPlaceHolders = $appendPlaceHolders;
        $this->page = $page;
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
        if (! $this->workflowId || ! is_array($this->workflowInfo)) {
            return false;
        }

        if ($this->workflowInfo['detail']['isActive'] == false) {
            \Log::info('WORKFLOW - Workflow is not active. Exiting.');

            return false;
        }

        \Log::info('WORKFLOW - Name: '.$this->workflowInfo['detail']['name']);

        $jobWorkflowId = $this->createJobWorkflowEntry($this->workflowId, $this->referenceId);
        if (! $jobWorkflowId) {
            return false;
        }
        setRunningJobWorkflowId($jobWorkflowId);

        setModuleForCurrentWorkflow($this->workflowInfo['detail']['module']);
        $allConditions = $this->workflowInfo['workFlowConditions'];
        $nextPageCommand = null;

        $graphQLQuery = [];
        // NEED TO FILTER DATA IF EFFECTIVE ACTION IS 'ON_DATE_TIME' AND EVENT CONFIGURED FOR FOLLOW UP EVENT
        // Example: After/Before X day(s)/month(s)/year(s) of the event
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

        foreach ($allConditions as $condition) {
            if (isset($condition['status']) && $condition['status'] === false) {
                \Log::info('WORKFLOW - Condition skipped (inactive): '.($condition['id'] ?? ''));

                continue;
            }

            $feedFile = '';
            $data = [];

            if ($condition['applyRuleTo'] == 'ALL') {
                // DO NOTHING
            }

            if ($condition['applyRuleTo'] == 'CUSTOM_FEED') {
                try {
                    $feedFile = $this->getFileOnLocal($condition['s3FilePath']);
                } catch (\Exception $e) {
                    \Log::error('WORKFLOW - Failed to download feed file from S3: '.$condition['s3FilePath']);
                    \Log::error('WORKFLOW - '.$e->getMessage());
                }
            }

            if ($condition['applyRuleTo'] == 'CERTAIN' && ! $this->isManuallyInvoked) {
                $conditionsToApply = GraphQLSchemaBuilderService::buildWhereConditionFromGroup($condition['applyConditionRules']);

                if (! empty($conditionsToApply)) {
                    if (count($graphQLQuery)) {
                        $graphQLQuery['JOIN'] = $conditionsToApply;
                    } else {
                        $graphQLQuery = $conditionsToApply;
                    }
                }
            }

            foreach ($condition['instanceActions'] as $action) {
                $data = [];
                $actionType = $action['actionType'];
                $actionPayload = $action['payload'];

                // Workflow Log
                WorkflowLog::create([
                    'job_workflow_id' => $jobWorkflowId ?: null,
                    'workflow_id' => $this->workflowId,
                    'record_identifier' => $this->recordIdentifier ?? null,
                    'module' => $this->workflowInfo['detail']['module'],
                    'status' => WorkflowLog::STATUS_IN_PROGRESS,
                    'action_type' => $actionType,
                ]);

                try {
                    $actionToExecute = $this->instantiateAction(
                        $actionType,
                        $actionPayload,
                        $this->workflowInfo['detail']['module'],
                        $this->workflowId,
                        $jobWorkflowId
                    );
                } catch (\RuntimeException $e) {
                    continue 2;
                }

                if (! $actionToExecute) {
                    \Log::error('WORKFLOW - Action not found: '.$actionType);

                    continue;
                }

                /***
                 * Placeholders data to extract from appendPlaceHolders
                 */
                $placeHolderWithValues = [];
                $placeHolderToExtract = [];
                if (count($this->appendPlaceHolders)) {
                    foreach ($this->appendPlaceHolders as $placeHolderKey => $placeHolderValue) {
                        if ($placeHolderValue) { // NO NEED TO EXTRACT IF VALUE IS ALREADY AVAILABLE
                            $placeHolderWithValues[$placeHolderKey] = $placeHolderValue;

                            continue;
                        }

                        $placeHolderToExtract[] = $placeHolderKey;
                    }
                }

                try {
                    [$listOfRequiredData, $listOfMandateData] = $this->resolveActionDataRequirements(
                        $actionToExecute,
                        $actionType,
                        $actionPayload,
                        $placeHolderToExtract
                    );
                } catch (\Exception $e) {
                    \Log::error('WORKFLOW - Error while getting required data for action - '.$actionType.' : '.$e->getMessage());

                    continue;
                }

                if ($this->isManuallyInvoked) {
                    $data[] = $this->data;
                } elseif (count($graphQLQuery) || count($listOfRequiredData)) {
                    $queryResult = $this->buildAndExecuteGraphQLQuery(
                        $this->workflowInfo['detail']['module'],
                        $this->appendPlaceHolders,
                        $listOfRequiredData,
                        $graphQLQuery,
                        true,
                        $this->workflowId,
                        $jobWorkflowId,
                        function ($moduleClassForGraphQL) {
                            $moduleClassForGraphQL->setPage($this->page);
                            $moduleClassForGraphQL->setQueryArgsContext(
                                $this->workflowInfo['when']['dateTimeInfoToExecuteWorkflow'] ?? []
                            );
                        }
                    );

                    if ($queryResult === null) {
                        continue;
                    }

                    $moduleClassForGraphQL = $queryResult['moduleClassForGraphQL'];
                    $fieldMapping = $queryResult['fieldMapping'];
                    $graphQLSchemaBuilder = $queryResult['graphQLSchemaBuilder'];
                    $queryArgs = $queryResult['queryArgs'];
                    $response = $queryResult['response'];

                    // If schema provides custom record extraction, use it directly (skip jqFilter)
                    if ($moduleClassForGraphQL->hasCustomRecordExtraction()) {
                        foreach ($moduleClassForGraphQL->getRecordsFromResponse($response) as $record) {
                            $record = array_merge($record, $placeHolderWithValues);
                            $data[] = $record;
                        }
                    } else {
                        if (empty(array_first($response))) {
                            \Log::debug('WORKFLOW - GraphQL unable to fetch the data');

                            continue;
                        }

                        try {
                            $parsedData = $this->parseGraphQLResponse(
                                $response,
                                $listOfRequiredData,
                                $fieldMapping,
                                $moduleClassForGraphQL,
                                $graphQLSchemaBuilder,
                                $this->workflowId,
                                $jobWorkflowId
                            );

                            $parsedData = array_merge($parsedData, $placeHolderWithValues);
                            $hasAtLeastOneValue = ! empty(array_filter($parsedData, fn ($v) => $v !== null && $v !== '' && $v !== false && $v !== 'null'));

                            if ($this->recordIdentifier && ! empty($parsedData) && ! $hasAtLeastOneValue) {
                                \Log::warning('WORKFLOW -  Data unavailable or all required fields are empty');
                                break 2;
                            }
                            if ($actionType == 'WEB_HOOK') {
                                $data = $this->generatePayloadFromParsedData($parsedData);
                            } else {
                                // SET DATA FOR ACTION
                                $data[] = $parsedData;
                            }
                        } catch (\Exception $e) {
                            $this->workflowService->addWorkflowLog(
                                $this->workflowId,
                                $jobWorkflowId,
                                'GRAPHQL_ERROR',
                                $e->getMessage()
                            );
                            \Log::error(
                                'WORKFLOW - Error while extracting data from GraphQL response - '.$e->getMessage(),
                                [
                                    'message' => $e->getMessage(),
                                    'file' => $e->getFile(),
                                    'line_no' => $e->getLine(),
                                ]
                            );

                            continue;
                        }
                    } // end else (jqFilter path)

                    if ($nextPageCommand === null) {
                        $nextPageArgs = $moduleClassForGraphQL->getNextPageArgs($response, $queryArgs);
                        if ($nextPageArgs !== null) {
                            $nextPageCommand = gitCommandToDispatchWorkflow(
                                $this->workflowId,
                                $this->recordIdentifier,
                                [],
                                $this->appendPlaceHolders,
                                $this->referenceId,
                                $this->page + 1
                            );
                        }
                    }
                }

                if (config('app.env') != 'production') {
                    \Log::info('WORKFLOW - data: ', $data);
                }

                try {
                    $data = $this->validateAndFilterData($data, $listOfMandateData, $action, $actionType, $this->workflowId, $jobWorkflowId, $placeHolderToExtract);

                    if ($data === false) {
                        continue;
                    }

                    $actionToExecute->setWorkflowData($this->workflowId, $jobWorkflowId, $this->recordIdentifier);
                    $actionToExecute->setDataForAction($feedFile, $data);
                    $actionToExecute->execute();
                } catch (\Exception $e) {
                    \Log::error('WORKFLOW - Error while executing action - '.$actionType.' : '.$e->getMessage());

                    continue;
                }
            }
            WorkflowLog::markWorkflowCompleted($this->workflowId, $jobWorkflowId);
        }

        if ($nextPageCommand !== null) {
            \Illuminate\Support\Facades\Artisan::call($nextPageCommand['command'], $nextPageCommand['options']);
        }

        return true;
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
