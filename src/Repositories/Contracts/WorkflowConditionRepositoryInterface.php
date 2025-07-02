<?php

namespace Taurus\Workflow\Repositories\Contracts;

use Taurus\Workflow\Models\WorkflowCondition;
use Illuminate\Support\Collection;

interface WorkflowConditionRepositoryInterface
{
    public function create(array $data): WorkflowCondition;
    public function getByWorkflowId(int $workflowId): Collection;
    public function update(int $id, array $data): ?WorkflowCondition;
    public function deleteWhereIn(string $column, array $values): bool;
    public function restoreWhereIn(array $ids): bool;
}
