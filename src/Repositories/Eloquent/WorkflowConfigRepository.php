<?php

namespace Taurus\Workflow\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Taurus\Workflow\Models\WorkflowConfig;
use Taurus\Workflow\Repositories\Contracts\WorkflowConfigRepositoryInterface;

class WorkflowConfigRepository implements WorkflowConfigRepositoryInterface
{
    protected $model;

    public function __construct(WorkflowConfig $model)
    {
        $this->model = $model;
    }

    public function all(): ?Collection
    {
        return $this->model->all(['id', 'config_key', 'config_value', 'last_checked']);
    }

    public function create(array $data): WorkflowConfig
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?WorkflowConfig
    {
        $WorkflowConfig = $this->model->findOrFail($id);
        $WorkflowConfig->update($data);

        return $WorkflowConfig;
    }

    public function getByKey(string $key): WorkflowConfig
    {
        return $this->model->where('config_key', $key)->firstOrFail();
    }
}
