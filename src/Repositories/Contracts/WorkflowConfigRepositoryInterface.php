<?php

namespace Taurus\Workflow\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Taurus\Workflow\Models\WorkflowConfig;

interface WorkflowConfigRepositoryInterface
{
    public function all(): ?Collection;

    public function getByKey(string $key): WorkflowConfig;

    public function create(array $data): WorkflowConfig;

    public function update(int $id, array $data): ?WorkflowConfig;
}
