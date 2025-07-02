<?php

namespace Taurus\Workflow\Repositories\Contracts;

use Taurus\Workflow\Models\WorkflowConfig;
use Illuminate\Database\Eloquent\Collection;

interface WorkflowConfigRepositoryInterface
{
    public function all(): ?Collection;
    public function getByKey(string $key): WorkflowConfig;
    public function create(array $data): WorkflowConfig;
    public function update(int $id, array $data): ?WorkflowConfig;
}
