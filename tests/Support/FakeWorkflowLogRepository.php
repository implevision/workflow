<?php

namespace Taurus\Workflow\Tests\Support;

use Taurus\Workflow\Models\WorkflowLog;
use Taurus\Workflow\Repositories\Contracts\WorkflowLogRepositoryInterface;

class FakeWorkflowLogRepository implements WorkflowLogRepositoryInterface
{
    public array $created = [];

    public array $completed = [];

    public function create(array $data): WorkflowLog
    {
        $this->created[] = $data;

        return new WorkflowLog($data);
    }

    public function markCompleted(int $workflowId, int $jobWorkflowId): void
    {
        $this->completed[] = [$workflowId, $jobWorkflowId];
    }
}
