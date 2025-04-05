<?php

namespace Taurus\Workflow\Repositories\Eloquent;

use Taurus\Workflow\Models\WorkflowCondition;
use Illuminate\Support\Collection;
use Taurus\Workflow\Repositories\Contracts\WorkflowConditionRepositoryInterface;

class WorkflowConditionRepository implements WorkflowConditionRepositoryInterface
{
    protected $model;

    public function __construct(WorkflowCondition $model)
    {
        $this->model = $model;
    }

    public function create(array $data): WorkflowCondition
    {
        return $this->model->create($data);
    }

    public function getByWorkflowId(int $workflowId): Collection
    {
        return $this->model->where('workflow_id', $workflowId)->get();
    }

    public function update(int $id, array $data): ?WorkflowCondition
    {
        $condition = $this->model->findOrFail($id);
        $condition->update($data);
        return $condition;
    }

    public function deleteWhereIn(string $column, array $values): bool
    {
        return $this->model->whereIn($column, $values)->delete() > 0;
    }

    public function restoreWhereIn(array $ids): bool
    {
        return $this->model->whereIn('id', $ids)->restore() > 0;
    }
}
