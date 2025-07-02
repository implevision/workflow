<?php

namespace Taurus\Workflow\Repositories\Contracts;

use Taurus\Workflow\Models\WorkflowAction;
use Illuminate\Database\Eloquent\Collection;

interface WorkflowActionRepositoryInterface
{
    public function create(array $data): WorkflowAction;
    public function getByConditionId(int $conditionId): Collection;
    public function update(int $id, array $data): ?WorkflowAction;
    public function deleteWhereIn(string $column, array $values): bool;
    public function restoreWhereIn(array $ids): bool;
}
