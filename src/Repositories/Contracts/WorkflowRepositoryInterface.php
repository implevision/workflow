<?php

namespace Taurus\Workflow\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Taurus\Workflow\Models\Workflow;

interface WorkflowRepositoryInterface
{
    public function all(): ?Collection;

    public function getById(int $id, bool $withDeleted = false): Workflow;

    public function create(array $data): Workflow;

    public function update(int $workflowId, array $data): ?Workflow;

    public function delete(int $workflowId): bool;

    public function restore(int $workflowId): bool;

    public function getMatchingWorkflow($entityType, $entityAction): ?array;
}
