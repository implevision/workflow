<?php

namespace Taurus\Workflow\Repositories\Contracts;

use Taurus\Workflow\Models\WorkflowLog;

interface WorkflowLogRepositoryInterface
{
    public function create(array $data): WorkflowLog;

    public function markCompleted(int $workflowId, int $jobWorkflowId): void;
}
