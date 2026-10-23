<?php

namespace Taurus\Workflow\Tests\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Taurus\Workflow\Models\WorkflowLog;
use Taurus\Workflow\Repositories\Contracts\WorkflowLogRepositoryInterface;

class FakeWorkflowLogRepository implements WorkflowLogRepositoryInterface
{
    public array $created = [];

    public array $completed = [];

    public ?WorkflowLog $logForJob = null;

    public array $completedLogs = ['data' => [], 'total' => 0];

    public ?Collection $dailyCounts = null;

    public ?Collection $completedWorkflows = null;

    public array $queries = [];

    public function create(array $data): WorkflowLog
    {
        $this->created[] = $data;

        return new WorkflowLog($data);
    }

    public function markCompleted(int $workflowId, int $jobWorkflowId): void
    {
        $this->completed[] = [$workflowId, $jobWorkflowId];
    }

    public function findForJob(?int $workflowId, ?int $jobWorkflowId): ?WorkflowLog
    {
        $this->queries[] = ['findForJob', $workflowId, $jobWorkflowId];

        return $this->logForJob;
    }

    public function getCompleted(string $module, int $limit, int $offset, ?int $workflowId = null): array
    {
        $this->queries[] = ['getCompleted', $module, $limit, $offset, $workflowId];

        return $this->completedLogs;
    }

    public function getDailyCompletedCounts(string $module, Carbon $from, Carbon $to, ?int $workflowId = null): Collection
    {
        $this->queries[] = ['getDailyCompletedCounts', $module, $from->toDateTimeString(), $to->toDateTimeString(), $workflowId];

        return $this->dailyCounts ?? collect();
    }

    public function getCompletedWorkflows(string $module): Collection
    {
        $this->queries[] = ['getCompletedWorkflows', $module];

        return $this->completedWorkflows ?? collect();
    }
}
