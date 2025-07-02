<?php

namespace Taurus\Workflow\Repositories\Eloquent;

use Taurus\Workflow\Models\WorkflowAction;
use Taurus\Workflow\Repositories\Contracts\WorkflowActionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WorkflowActionRepository implements WorkflowActionRepositoryInterface
{
    protected $model;

    public function __construct(WorkflowAction $model)
    {
        $this->model = $model;
    }

    public function create(array $data): WorkflowAction
    {
        return $this->model->create($data);
    }

    public function getByConditionId(int $conditionId): Collection
    {
        return $this->model->where('condition_id', $conditionId)->get();
    }

    public function update(int $id, array $data): ?WorkflowAction
    {
        $action = $this->model->find($id);
        $action->update($data);
        return $action;
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
