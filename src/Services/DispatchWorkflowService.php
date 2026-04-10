<?php

namespace Taurus\Workflow\Services;

use Taurus\Workflow\Models\WorkflowLog;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

class DispatchWorkflowService extends AbstractDispatchService
{
    private $workflowId;

    private $workflowInfo = null;

    protected $isWorkflowLive;

    protected $recordIdentifier;

    protected $data;

    protected $appendPlaceHolders;

    protected $isManuallyInvoked = false;

    /**
     * @param  int  $workflowId  The ID of the workflow to be dispatched.
     * @param  int|string  $recordIdentifier  An optional identifier for the record, default is 0.
     */
    public function __construct(int $workflowId, int|string $recordIdentifier = 0, $data = [], $appendPlaceHolders = [])
    {
        $this->workflowId = $workflowId;
        $this->recordIdentifier = $recordIdentifier;
        $this->data = $data;
        $this->appendPlaceHolders = $appendPlaceHolders;
        $this->isManuallyInvoked = count($data) ? true : false;
        $this->logPrefix = 'WORKFLOW';
        $this->initializeServices();
        $this->getInfo();
    }

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

        $jobWorkflowId = $this->createJobWorkflowEntry($this->workflowId);
        if (! $jobWorkflowId) {
            return false;
        }
        setRunningJobWorkflowId($jobWorkflowId);

        setModuleForCurrentWorkflow($this->workflowInfo['detail']['module']);
        $allConditions = $this->workflowInfo['workFlowConditions'];

        $graphQLQuery = [];
        // NEED TO FILTER DATA IF EFFECTIVE ACTION IS 'ON_DATE_TIME' AND EVENT CONFIGURED FOR FOLLOW UP EVENT
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

                if (count($graphQLQuery)) {
                    $graphQLQuery['JOIN'] = $conditionsToApply;
                } else {
                    $graphQLQuery = $conditionsToApply;
                }
            }

            foreach ($condition['instanceActions'] as $action) {
                $data = []; // TODO review later with jimish sir
                $actionToExecute = null;
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

                // Create action via factory
                $actionToExecute = null;
                try {
                    $actionToExecute = $this->createAndInitializeAction($actionType, $actionPayload);
                } catch (\InvalidArgumentException $e) {
                    \Log::error('WORKFLOW - '.$e->getMessage());

                    continue;
                } catch (\Exception $e) {
                    $this->workflowService->addWorkflowLog(
                        $this->workflowId,
                        $jobWorkflowId,
                        'ERROR_INITIATING_ACTION',
                        $e->getMessage()
                    );
                    \Log::error("WORKFLOW - Error while initiating {$actionType} action. ".$e->getMessage());

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

                // Resolve data requirements via shared method
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
                    try {
                        $parsedData = $this->fetchAndParseGraphQLData(
                            $this->workflowInfo['detail']['module'],
                            $listOfRequiredData,
                            $graphQLQuery
                        );

                        $parsedData = array_merge($parsedData, $placeHolderWithValues);

                        if ($actionType == 'WEB_HOOK') {
                            $data = $this->generatePayloadFromParsedData($parsedData);
                        } else {
                            $data[] = $parsedData;
                        }
                    } catch (\Exception $e) {
                        $this->workflowService->addWorkflowLog(
                            $this->workflowId,
                            $jobWorkflowId,
                            'GRAPHQL_ERROR',
                            $e->getMessage()
                        );
                        \Log::error('WORKFLOW - Error in GraphQL data resolution - '.$e->getMessage());

                        continue;
                    }
                }

                if (config('app.env') != 'production') {
                    \Log::info('WORKFLOW - data: ', $data);
                }

                try {
                    $hasPriorDataForWorkflow = $this->validateAndResolveData(
                        $data,
                        $listOfMandateData,
                        $actionType,
                        $actionPayload,
                        $this->workflowId,
                        $jobWorkflowId
                    );

                    if ($hasPriorDataForWorkflow === false && count($data) == 0) {
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

        return true;
    }
}
