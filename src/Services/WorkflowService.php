<?php

namespace Taurus\Workflow\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;
use Taurus\Workflow\Consumer\ConsumerService;
use Taurus\Workflow\Data\WorkflowData;
use Taurus\Workflow\Repositories\Contracts\JobWorkflowRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowActionRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowConditionRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowConfigRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;
use Taurus\Workflow\Services\AWS\EventBridgeScheduler;

class WorkflowService
{
    protected $workflowRepo;

    protected $workflowConditionRepo;

    protected $workflowActionRepo;

    protected $jobWorkflowRepo;

    protected $workflowConfigRepo;

    public function __construct(
        WorkflowRepositoryInterface $workflowRepo,
        WorkflowConditionRepositoryInterface $workflowConditionRepo,
        WorkflowActionRepositoryInterface $workflowActionRepo,
        JobWorkflowRepositoryInterface $jobWorkflowRepo,
        WorkflowConfigRepositoryInterface $workflowConfigRepo
    ) {
        $this->workflowRepo = $workflowRepo;
        $this->workflowConditionRepo = $workflowConditionRepo;
        $this->workflowActionRepo = $workflowActionRepo;
        $this->jobWorkflowRepo = $jobWorkflowRepo;
        $this->workflowConfigRepo = $workflowConfigRepo;
    }

    public function getAllWorkflows()
    {
        return $this->workflowRepo->all();
    }

    /**
     * Create a new workflow resource.
     *
     * @param  WorkflowData  $data  The workflow data.
     * @return Workflow The created workflow.
     *
     * @throws \Exception If failed to save the workflow.
     */
    public function createWorkflow(WorkflowData $data)
    {
        DB::beginTransaction();
        try {
            $data = $data->toArray();
            $workflowNextDateToExecute = null;
            $workflowExecutionFrequency = 'ONCE';
            if ($data['when']['effectiveActionToExecuteWorkflow'] == 'ON_DATE_TIME') {
                if (! empty($data['when']['dateTimeInfoToExecuteWorkflow']['recurringFrequency'])) {
                    $workflowNextDateToExecute = date('Y-m-d', strtotime('+1 day'));
                    $workflowExecutionFrequency = 'RECURRING';
                } elseif (! empty($data['when']['dateTimeInfoToExecuteWorkflow']['executionEffectiveDate'])) {
                    $workflowNextDateToExecute = date('Y-m-d', strtotime($data['when']['dateTimeInfoToExecuteWorkflow']['executionEffectiveDate']));
                }
            }

            $workflow = $this->workflowRepo->create([
                'module' => $data['detail']['module'],
                'name' => $data['detail']['name'],
                'description' => $data['detail']['description'],
                'effective_action_to_execute_workflow' => $data['when']['effectiveActionToExecuteWorkflow'] ?? null,
                'record_action_to_execute_workflow' => $data['when']['recordActionToExecuteWorkflow'] ?? null,
                'date_time_info_to_execute_workflow' => $data['when']['dateTimeInfoToExecuteWorkflow'] ?? [],
                'workflow_execution_frequency' => $workflowExecutionFrequency,
                'workflow_next_date_to_execute' => $workflowNextDateToExecute,
                'is_active' => $data['detail']['isActive'] ?? true,
            ]);

            if (! empty($data['workFlowConditions'])) {
                foreach ($data['workFlowConditions'] as $condition) {
                    $instanceActions = $condition['instanceActions'] ?? [];
                    unset($condition['instanceActions']);
                    unset($condition['id']);

                    $conditionEntry = $this->workflowConditionRepo->create([
                        'workflow_id' => $workflow->id,
                        'conditions' => $condition ?? [],
                    ]);

                    // Save workflow actions related to the condition
                    if (! empty($instanceActions)) {
                        foreach ($instanceActions as $action) {
                            $action = is_array($action) ? $action : $action->toArray();
                            unset($action['id']);
                            $this->workflowActionRepo->create([
                                'condition_id' => $conditionEntry->id,
                                'payload' => $action ?? [],
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            if (! empty($data['when']['customDateTimeInfoToExecuteWorkflow'])) {
                $workflowId = $workflow->id;
                $workflows = $this->workflowRepo->getById($workflowId)->toArray();
                $this->scheduleWorkflows($workflows);
            }

            return $workflow;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Failed to save workflow: '.$e->getMessage());
        }
    }

    /**
     * Retrieve detailed information of a workflow by its ID.
     *
     * @param  int  $workflowId  The ID of the workflow to retrieve.
     * @param  bool  $withDeleted  weather to include deleted workflow.
     * @return array An array containing the workflow details, conditions, and actions.
     *
     * @throws \Exception If the no data found for the provided workflow ID.
     */
    public function getWorkflowDetailsById(int $workflowId, bool $withDeleted = false): WorkflowData
    {
        try {
            $workflow = $this->workflowRepo->getById($workflowId, $withDeleted);
        } catch (\Exception $e) {
            throw new \Exception('No data found for the provided workflow ID: '.$workflowId);
        }

        $workflowConditions = [];

        foreach ($workflow->conditions as $condition) {
            $conditions = $condition->conditions;
            $applyRuleTo = $conditions['applyRuleTo'] ?? '';
            unset($conditions['applyRuleTo']);

            $workflowConditions[] = [
                'id' => $condition->id,
                'applyRuleTo' => $applyRuleTo,
                's3FilePath' => $conditions['s3FilePath'] ?? null,
                'applyConditionRules' => $conditions['applyConditionRules'] ?? [],
                'instanceActions' => $condition->actions->map(function ($action) {
                    $payload = $action->payload ?? [];

                    return [
                        'id' => $action->id,
                        'actionType' => $payload['actionType'] ?? '',
                        'payload' => $payload['payload'] ?? [],
                    ];
                })->toArray(),
            ];
        }

        return WorkflowData::fromArray([
            'id' => $workflow->id,
            'awsEventBridgeArn' => $workflow->aws_event_bridge_arn,
            'detail' => [
                'module' => $workflow->module,
                'name' => $workflow->name,
                'description' => $workflow->description,
                'isActive' => $workflow->is_active,
            ],
            'when' => [
                'effectiveActionToExecuteWorkflow' => $workflow->effective_action_to_execute_workflow,
                'recordActionToExecuteWorkflow' => $workflow->record_action_to_execute_workflow,
                'dateTimeInfoToExecuteWorkflow' => $workflow->date_time_info_to_execute_workflow,
            ],
            'workFlowConditions' => $workflowConditions,
        ]);
    }

    /**
     * Update a workflow given the new data.
     *
     * @param  WorkflowData  $data  The new workflow data.
     * @return Workflow The updated workflow.
     *
     * @throws \Exception If failed to save the workflow.
     */
    public function updateWorkflow(WorkflowData $data)
    {
        DB::beginTransaction();
        try {
            $data = $data->toArray();
            // Update Workflow Table
            $workflow = $this->workflowRepo->update($data['id'], [
                'module' => $data['detail']['module'],
                'name' => $data['detail']['name'],
                'description' => $data['detail']['description'],
                'effective_action_to_execute_workflow' => $data['when']['effectiveActionToExecuteWorkflow'] ?? null,
                'record_action_to_execute_workflow' => $data['when']['recordActionToExecuteWorkflow'] ?? null,
                'date_time_info_to_execute_workflow' => $data['when']['dateTimeInfoToExecuteWorkflow'] ?? [],
                // TODO: UPDATE workflow_execution_frequency & workflow_next_date_to_execute
            ]);

            // get existing condition IDs
            $existingConditionIds = $this->workflowConditionRepo->getByWorkflowId($data['id'])->pluck('id')->toArray();
            $newConditionIds = []; // incoming in request

            if (! empty($data['workFlowConditions'])) {
                foreach ($data['workFlowConditions'] as $condition) {
                    $instanceActions = $condition['instanceActions'] ?? [];
                    unset($condition['instanceActions']);

                    $conditionId = $condition['id'] ?? null;
                    unset($condition['id']);

                    // Update or Create Condition
                    if (! empty($conditionId)) {
                        $conditionEntry = $this->workflowConditionRepo->update($conditionId, [
                            'workflow_id' => $workflow->id,
                            'conditions' => $condition ?? [],
                        ]);
                        $newConditionIds[] = $conditionId;
                    } else {
                        $conditionEntry = $this->workflowConditionRepo->create([
                            'workflow_id' => $workflow->id,
                            'conditions' => $condition ?? [],
                        ]);
                        $newConditionIds[] = $conditionEntry->id;
                    }

                    // get existing condition IDs
                    $existingActionIds = $this->workflowActionRepo->getByConditionId($conditionEntry->id)->pluck('id')->toArray();
                    $newActionIds = []; // incoming in request

                    // Update or Create Actions
                    if (! empty($instanceActions)) {
                        foreach ($instanceActions as $action) {
                            $action = is_array($action) ? $action : $action->toArray();
                            $actionId = $action['id'] ?? null;
                            unset($action['id']); // unset if exist

                            if (! empty($action['id'])) {
                                $this->workflowActionRepo->update($actionId, [
                                    'condition_id' => $conditionEntry->id,
                                    'payload' => $action ?? [],
                                ]);
                                $newActionIds[] = $actionId;
                            } else {
                                $actionEntry = $this->workflowActionRepo->create([
                                    'condition_id' => $conditionEntry->id,
                                    'payload' => $action ?? [],
                                ]);
                                $newActionIds[] = $actionEntry->id;
                            }
                        }
                    }

                    // Delete old actions which is not in request
                    $actionsToDelete = array_diff($existingActionIds, $newActionIds);
                    if (! empty($actionsToDelete)) {
                        $this->workflowActionRepo->deleteWhereIn('id', $actionsToDelete);
                    }
                }
            }

            // Delete old actions which is not in request
            $conditionsToDelete = array_diff($existingConditionIds, $newConditionIds);
            if (! empty($conditionsToDelete)) {
                $this->workflowConditionRepo->deleteWhereIn('id', $conditionsToDelete);
            }

            DB::commit();

            return $workflow;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Failed to save workflow: '.$e->getMessage());
        }
    }

    /**
     * Retrieve a workflow and its associated condition and action IDs by workflow ID.
     *
     * @param  int  $workflowId  The ID of the workflow to retrieve.
     * @return array An associative array containing the workflow ID, condition IDs, and action IDs.
     *
     * @throws \Exception If the workflow retrieval fails.
     */
    public function getWorkflowById(int $workflowId, bool $withDeleted = false): array
    {
        $workflow = $this->workflowRepo->getById($workflowId, $withDeleted);
        $conditionIds = $workflow->conditions->pluck('id')->toArray();
        $actionIds = $workflow->conditions->flatMap(fn ($condition) => $condition->actions->pluck('id'))->toArray();

        return [
            'id' => $workflow->id,
            'conditionIds' => $conditionIds,
            'actionIds' => $actionIds,
        ];
    }

    /**
     * Delete a specific workflow resource by its ID.
     *
     * @param  int  $workflowId  The ID of the workflow to delete.
     * @return bool A boolean indicating whether the workflow is deleted successfully or not.
     *
     * @throws \Exception If any error occurs during the deletion process.
     */
    public function deleteWorkflow(int $workflowId): bool
    {
        DB::beginTransaction();
        try {
            $workflowInfo = $this->getWorkflowById($workflowId);
            $conditionIds = $workflowInfo['conditionIds'];
            $actionIds = $workflowInfo['actionIds'];

            // delete conditions related to workflow
            if (! empty($conditionIds)) {
                $this->workflowConditionRepo->deleteWhereIn('id', $conditionIds);
            }

            // delete actions related to workflow
            if (! empty($actionIds)) {
                $this->workflowActionRepo->deleteWhereIn('id', $actionIds);
            }

            // delete workflow
            $this->workflowRepo->delete($workflowId);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Failed to delete workflow: '.$e->getMessage());
        }
    }

    /**
     * Restore a deleted workflow by its ID.
     *
     * @param  int  $workflowId  The ID of the workflow to restore.
     * @return bool A boolean indicating whether the workflow is restored successfully or not.
     *
     * @throws \Exception If any error occurs during the restoration process.
     */
    public function restoreWorkflow(int $workflowId): bool
    {
        DB::beginTransaction();
        try {
            $workflowInfo = $this->getWorkflowById($workflowId, true);
            $conditionIds = $workflowInfo['conditionIds'];
            $actionIds = $workflowInfo['actionIds'];

            // restore conditions related to workflow
            if (! empty($conditionIds)) {
                $this->workflowConditionRepo->restoreWhereIn($conditionIds);
            }

            // restore actions related to workflow
            if (! empty($actionIds)) {
                $this->workflowActionRepo->restoreWhereIn($actionIds);
            }

            // restore workflow
            $this->workflowRepo->restore($workflowId);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Failed to restore workflow: '.$e->getMessage());
        }
    }

    public function calculateAndUpdateNextExecution(int $workflowId): ?string
    {
        $workflow = $this->workflowRepo->getById($workflowId);

        return $workflow->calculateAndUpdateNextExecution()->toDateTimeString();
    }

    public function getWorkflowsExecutingToday(): array
    {
        return $this->workflowRepo->getScheduledForToday();
    }

    /**
     * Retrieves the matching workflow for the given entity type, entity action, and entity.
     *
     * @param  string  $entityType  The type of the entity.
     * @param  string  $entityAction  The action performed on the entity.
     * @param  mixed  $entity  The entity object.
     * @return array|bool The matching workflow, or false if no matching workflow is found.
     */
    public function getMatchingWorkflow($entityType, $entityAction, $entity): bool|array
    {
        $matchedWorkflow = $this->workflowRepo->getMatchingWorkflow($entityType, $entityAction);
        if (empty($matchedWorkflow)) {
            return false;
        }

        return $this->findMatchingWorkflowForEntity($entityType, $entity, $matchedWorkflow);
    }

    /**
     * Finds the matching workflow for the given entity.
     *
     * @param  mixed  $entity  The entity to find the matching workflow for.
     * @param  mixed  $matchedWorkflow  The matched workflow for the entity.
     */
    public function findMatchingWorkflowForEntity($entityType, $entity, $matchedWorkflow): array
    {
        $workflowToRun = [];
        foreach ($matchedWorkflow as $workflow) {
            foreach ($workflow['conditions'] as $conditions) {
                if ($conditions['conditions']['applyRuleTo'] == 'CERTAIN') {
                    $entityData = $entityType::find($entity);
                    // TODO: Call workflow engine to check if the condition is met
                } else {
                    array_push($workflowToRun, $workflow['id']);
                }
            }
        }

        return $workflowToRun;
    }

    public function createScheduleGroupToExecuteWorkflow($groupName)
    {
        $tags = getScheduleGroupTagsToExecuteWorkflow();

        try {
            return EventBridgeScheduler::createScheduleGroup($groupName, $tags);
        } catch (\Throwable $e) {
            \Log::error('Error creating schedule group: '.$e->getMessage());

            return false;
        }
    }

    public function createScheduleToExecuteWorkflow($groupName, $workflowId, $scheduleExpression)
    {
        // TODO: pass record identifier if any
        $commandToRunWorkflow = getCliCommandToDispatchWorkflow($workflowId);

        try {
            $target = [
                'arn' => config('workflow.aws_lambda_function_arn_to_invoke_workflow'),
                'roleArn' => config('workflow.aws_iam_role_arn_to_invoke_lambda_from_event_bridge'),
                'input' => json_encode([
                    'task_definition' => config('workflow.task_definition'),
                    'command' => $commandToRunWorkflow,
                ]),
            ];
            $scheduleGroupName = getEventSchedulerNameToExecuteWorkflow($workflowId);

            return EventBridgeScheduler::createSchedule($scheduleGroupName, $scheduleExpression, $target, $groupName);
        } catch (\Throwable $e) {
            \Log::error('Error creating schedule: '.$e->getMessage());

            return false;
        }
    }

    public function scheduleWorkflows($workflows)
    {
        foreach ($workflows as $workflow) {
            $groupName = getEventSchedulerGroupNameToExecuteWorkflow();

            if (in_array($workflow['effective_action_to_execute_workflow'], ['ON_DATE_TIME', 'CUSTOM_DATE_AND_TIME'])) {
                $scheduleGroupsArn = null;
                try {
                    $scheduleGroupArnObject = $this->workflowConfigRepo->getByKey('schedule_group_arn');
                    $scheduleGroupsArn = $scheduleGroupArnObject->config_value ?? null;
                } catch (\Exception $e) {
                    \Log::error('Error fetching schedule group ARN: '.$e->getMessage());
                }

                $isAwsInfraAlreadySetup = $scheduleGroupsArn ? true : false;

                if (! $isAwsInfraAlreadySetup) {
                    $scheduleGroupsArn = $this->createScheduleGroupToExecuteWorkflow($groupName);

                    if (! $scheduleGroupsArn) {
                        return false;
                    }

                    $this->workflowConfigRepo->create(
                        [
                            'config_key' => 'schedule_group_arn',
                            'config_value' => $scheduleGroupsArn,
                            'last_checked_at' => now(),
                        ]
                    );
                }

                if (! $workflow['aws_event_bridge_arn']) {
                    if ($workflow['effective_action_to_execute_workflow'] == 'ON_DATE_TIME') {
                        // RUNNING WORKFLOW AT PARTICULAR TIME
                        if (! empty($workflow['date_time_info_to_execute_workflow']['executionEffectiveDate'])) {
                            $executionDateTime = sprintf(
                                '%s %s:00',
                                $workflow['date_time_info_to_execute_workflow']['executionEffectiveDate'],
                                $workflow['date_time_info_to_execute_workflow']['executionEffectiveTime']
                            );

                            $configureTimeForEventSchedulerToAwakeWorkflowSystem = convertLocalToUTC($executionDateTime, 'm/d/Y H:i:s', config('workflow.timezone'));
                            $configureTimeForEventSchedulerToAwakeWorkflowSystem = 'at('.$configureTimeForEventSchedulerToAwakeWorkflowSystem.')'; // At specific date and time
                        } elseif (! empty($workflow['date_time_info_to_execute_workflow']['recurringFrequency'])) {
                            if ($workflow['date_time_info_to_execute_workflow']['recurringFrequency'] == 'WEEK') { // SCHEDULE RECURRING WORKFLOW
                                $configureTimeForEventSchedulerToAwakeWorkflowSystem = 'cron(0 0 ? * MON *)'; // At 00:00 on every Monday
                            } elseif ($workflow['date_time_info_to_execute_workflow']['recurringFrequency'] == 'MONTH') {
                                $configureTimeForEventSchedulerToAwakeWorkflowSystem = 'cron(0 0 1 * ? *)'; // At 00:00 on the first day of every month
                            } elseif ($workflow['date_time_info_to_execute_workflow']['recurringFrequency'] == 'YEAR') {
                                $configureTimeForEventSchedulerToAwakeWorkflowSystem = 'cron(0 0 1 1 ? *)'; // At 00:00 on the first day of January every year
                            } else {
                                $configureTimeForEventSchedulerToAwakeWorkflowSystem = 'cron(0 12 * * ? *)'; // At 00:00 every day
                            }
                        }
                    } elseif ($workflow['effective_action_to_execute_workflow'] == 'CUSTOM_DATE_AND_TIME') {
                        $cronJobArray = $workflow['custom_date_time_info_to_execute_workflow'];
                        $configureTimeForEventSchedulerToAwakeWorkflowSystem = sprintf(
                            'cron(%s %s %s %s %s %s)',
                            $cronJobArray['cronMinutes'],
                            $cronJobArray['cronHours'],
                            $cronJobArray['cronDayOfMonth'],
                            $cronJobArray['cronMonth'],
                            $cronJobArray['cronDayOfWeek'],
                            $cronJobArray['cronYear']
                        );
                    }

                    // SCHEDULE IN EVENT BRIDGE ONLY ONCE
                    $scheduleObj = $this->createScheduleToExecuteWorkflow($groupName, $workflow['id'], $configureTimeForEventSchedulerToAwakeWorkflowSystem);

                    if ($scheduleObj) {
                        $this->workflowRepo->update($workflow['id'], [
                            'aws_event_bridge_arn' => $scheduleObj['ScheduleArn'] ?? null,
                        ]);
                    }
                }

                if (! empty($workflow['date_time_info_to_execute_workflow']['recurringFrequency'])) {
                    if ($workflow['date_time_info_to_execute_workflow']['recurringFrequency'] == 'WEEK') { // SCHEDULE RECURRING WORKFLOW
                        $nextDateToRun = Carbon::now()->modify('next Monday')->format('Y-m-d '); // At 00:00 on every Monday
                    } elseif ($workflow['date_time_info_to_execute_workflow']['recurringFrequency'] == 'MONTH') {
                        $nextDateToRun = Carbon::now()->modify('next Month')->startOfMonth()->format('Y-m-d'); // At 00:00 on the first day of every month
                    } elseif ($workflow['date_time_info_to_execute_workflow']['recurringFrequency'] == 'YEAR') {
                        $nextDateToRun = Carbon::now()->modify('first day of January next year')->format('Y-m-d'); // At 00:00 on the first day of January every year
                    } else {
                        $nextDateToRun = Carbon::now()->modify('next day')->format('Y-m-d'); // At 00:00 every day
                    }

                    $this->workflowRepo->update($workflow['id'], [
                        'workflow_next_date_to_execute' => $nextDateToRun,
                    ]);
                }
            }
        }
    }

    public function getWorkflowStats($workflowId)
    {
        try {
            $this->jobWorkflowRepo->getInfo($workflowId);
        } catch (\Exception $exception) {
            \Log::error('Error getting workflow stats: '.$exception->getMessage());

            return [];
        }
    }

    public function getQueryForEffectiveAction($module, $executionFrequency, $executionFrequencyType, $executionEventIncident, $executionEvent)
    {
        $moduleService = $this->getModuleService($module);

        if ($moduleService instanceof \stdClass) {
            return [];
        }

        // This method should implement the logic to get matching records based on the effective action
        // For now, it returns an empty array as a placeholder
        return $moduleService->getQueryForEffectiveAction(
            $module,
            $executionFrequency,
            $executionFrequencyType,
            $executionEventIncident,
            $executionEvent
        );
    }

    public function getQueryForRecordIdentifier($module, $recordIdentifier)
    {
        $moduleService = $this->getModuleService($module);

        return $moduleService->getQueryForRecordIdentifier($module, $recordIdentifier);
    }

    private function getModuleService($module)
    {
        try {
            $consumerService = $this->getConsumerService();
            if ($consumerService instanceof \stdClass) {
                return new stdClass;
            }

            return $consumerService->getModuleService($module);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return new stdClass;
        }
    }

    public function getGraphQLQueryMappingService($module)
    {
        try {
            $consumerService = $this->getConsumerService();
            if ($consumerService instanceof \stdClass) {
                return new stdClass;
            }

            return $consumerService->getGraphQLQueryMappingService($module);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return new stdClass;
        }
    }

    public function getPostActionService()
    {
        try {
            $consumerService = $this->getConsumerService();
            if ($consumerService instanceof \stdClass) {
                return new stdClass;
            }

            return $consumerService->getPostActionService();
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return new stdClass;
        }
    }

    private function getConsumerService()
    {
        try {
            return ConsumerService::init();
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return new stdClass;
        }
    }
}
