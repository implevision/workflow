<?php

namespace Taurus\Workflow\Services;

use Taurus\Workflow\Data\WorkflowData;
use Illuminate\Support\Facades\DB;
use Taurus\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowActionRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowConditionRepositoryInterface;

class WorkflowService
{
    protected $workflowRepo;
    protected $workflowConditionRepo;
    protected $workflowActionRepo;

    public function __construct(
        WorkflowRepositoryInterface $workflowRepo,
        WorkflowConditionRepositoryInterface $workflowConditionRepo,
        WorkflowActionRepositoryInterface $workflowActionRepo,
    ) {
        $this->workflowRepo = $workflowRepo;
        $this->workflowConditionRepo = $workflowConditionRepo;
        $this->workflowActionRepo = $workflowActionRepo;
    }

    public function getAllWorkflows()
    {
        return $this->workflowRepo->all();
    }

    /**
     * Create a new workflow resource.
     *
     * @param WorkflowData $data The workflow data.
     * @return Workflow The created workflow.
     * @throws \Exception If failed to save the workflow.
     */
    public function createWorkflow(WorkflowData $data)
    {
        DB::beginTransaction();
        try {
            $data = $data->toArray();
            $workflow = $this->workflowRepo->create([
                'module' => $data['detail']['module'],
                'name' => $data['detail']['name'],
                'description' => $data['detail']['description'],
                'effective_action_to_execute_workflow' => $data['when']['effectiveActionToExecuteWorkflow'] ?? null,
                'record_action_to_execute_workflow' => $data['when']['recordActionToExecuteWorkflow'] ?? null,
                'date_time_info_to_execute_workflow' => $data['when']['dateTimeInfoToExecuteWorkflow'] ?? [],
                'workflow_execution_frequency' => 'ONCE',
            ]);

            if (!empty($data['workFlowConditions'])) {
                foreach ($data['workFlowConditions'] as $condition) {
                    $instanceActions = $condition['instanceActions'] ?? [];
                    unset($condition['instanceActions']);
                    unset($condition['id']);

                    $conditionEntry = $this->workflowConditionRepo->create([
                        'workflow_id' => $workflow->id,
                        'conditions' => $condition ?? [],
                    ]);

                    // Save workflow actions related to the condition
                    if (!empty($instanceActions)) {
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
            return $workflow;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Failed to save workflow: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve detailed information of a workflow by its ID.
     *
     * @param int $workflowId The ID of the workflow to retrieve.
     * @param bool $withDeleted weather to include deleted workflow.
     * @return array An array containing the workflow details, conditions, and actions.
     * @throws \Exception If the no data found for the provided workflow ID.
     */
    public function getWorkflowDetailsById(int $workflowId, bool $withDeleted = false): WorkflowData
    {
        $workflow = $this->workflowRepo->getById($workflowId, $withDeleted);

        $workflowConditions = [];

        foreach ($workflow->conditions as $condition) {
            $conditions = $condition->conditions;
            $applyRuleTo = $conditions['applyRuleTo'] ?? '';
            unset($conditions['applyRuleTo']);

            $workflowConditions[] = [
                'id' => $condition->id,
                'applyRuleTo' => $applyRuleTo,
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
            'detail' => [
                'module' => $workflow->module,
                'name' => $workflow->name,
                'description' => $workflow->description,
            ],
            'when' => [
                'effectiveActionToExecuteWorkflow' => $workflow->effective_action_to_execute_workflow,
                'recordActionToExecuteWorkflow' => $workflow->record_action_to_execute_workflow,
                'dateTimeInfoToExecuteWorkflow' => $workflow->date_time_info_to_execute_workflow,
            ],
            'workFlowConditions' => $workflowConditions
        ]);
    }

    /**
     * Update a workflow given the new data.
     *
     * @param WorkflowData $data The new workflow data.
     * @return Workflow The updated workflow.
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
                'workflow_execution_frequency' => 'ONCE',
            ]);

            // get existing condition IDs
            $existingConditionIds = $this->workflowConditionRepo->getByWorkflowId($data['id'])->pluck('id')->toArray();
            $newConditionIds = []; // incoming in request

            if (!empty($data['workFlowConditions'])) {
                foreach ($data['workFlowConditions'] as $condition) {
                    $instanceActions = $condition['instanceActions'] ?? [];
                    unset($condition['instanceActions']);

                    $conditionId = $condition['id'] ?? null;
                    unset($condition['id']);

                    // Update or Create Condition
                    if (!empty($conditionId)) {
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
                    if (!empty($instanceActions)) {
                        foreach ($instanceActions as $action) {
                            $action = is_array($action) ? $action : $action->toArray();
                            $actionId = $action['id'] ?? null;
                            unset($action['id']); // unset if exist

                            if (!empty($action['id'])) {
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
                    if (!empty($actionsToDelete)) {
                        $this->workflowActionRepo->deleteWhereIn('id', $actionsToDelete);
                    }
                }
            }

            // Delete old actions which is not in request
            $conditionsToDelete = array_diff($existingConditionIds, $newConditionIds);
            if (!empty($conditionsToDelete)) {
                $this->workflowConditionRepo->deleteWhereIn('id', $conditionsToDelete);
            }

            DB::commit();
            return $workflow;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Failed to save workflow: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a workflow and its associated condition and action IDs by workflow ID.
     *
     * @param int $workflowId The ID of the workflow to retrieve.
     * @return array An associative array containing the workflow ID, condition IDs, and action IDs.
     * @throws \Exception If the workflow retrieval fails.
     */
    public function getWorkflowById(int $workflowId, bool $withDeleted = false): array
    {
        $workflow = $this->workflowRepo->getById($workflowId, $withDeleted);
        $conditionIds = $workflow->conditions->pluck('id')->toArray();
        $actionIds = $workflow->conditions->flatMap(fn($condition) => $condition->actions->pluck('id'))->toArray();

        return [
            'id' => $workflow->id,
            'conditionIds' => $conditionIds,
            'actionIds' => $actionIds,
        ];
    }

    /**
     * Delete a specific workflow resource by its ID.
     *
     * @param int $workflowId The ID of the workflow to delete.
     * @return bool A boolean indicating whether the workflow is deleted successfully or not.
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
            if (!empty($conditionIds)) {
                $this->workflowConditionRepo->deleteWhereIn('id', $conditionIds);
            }

            // delete actions related to workflow
            if (!empty($actionIds)) {
                $this->workflowActionRepo->deleteWhereIn('id', $actionIds);
            }

            // delete workflow
            $this->workflowRepo->delete($workflowId);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Failed to delete workflow: ' . $e->getMessage());
        }
    }

    /**
     * Restore a deleted workflow by its ID.
     *
     * @param int $workflowId The ID of the workflow to restore.
     * @return bool A boolean indicating whether the workflow is restored successfully or not.
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
            if (!empty($conditionIds)) {
                $this->workflowConditionRepo->restoreWhereIn($conditionIds);
            }

            // restore actions related to workflow
            if (!empty($actionIds)) {
                $this->workflowActionRepo->restoreWhereIn($actionIds);
            }

            // restore workflow
            $this->workflowRepo->restore($workflowId);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Failed to restore workflow: ' . $e->getMessage());
        }
    }
}
