<?php

namespace Taurus\Workflow\Repositories\Eloquent;

use Taurus\Workflow\Models\WorkflowLog;
use Taurus\Workflow\Repositories\Contracts\WorkflowLogRepositoryInterface;

class WorkflowLogRepository implements WorkflowLogRepositoryInterface
{
    protected $model;

    public function __construct(WorkflowLog $model)
    {
        $this->model = $model;
    }

    public function create(array $data): WorkflowLog
    {
        return $this->model->create($data);
    }

    public function markCompleted(int $workflowId, int $jobWorkflowId): void
    {
        $this->model::markWorkflowCompleted($workflowId, $jobWorkflowId);
    }
}
