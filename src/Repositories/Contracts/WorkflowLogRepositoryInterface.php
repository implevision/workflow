<?php

namespace Taurus\Workflow\Repositories\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Taurus\Workflow\Models\WorkflowLog;

interface WorkflowLogRepositoryInterface
{
    public function create(array $data): WorkflowLog;

    public function markCompleted(int $workflowId, int $jobWorkflowId): void;

    public function findForJob(?int $workflowId, ?int $jobWorkflowId): ?WorkflowLog;

    public function getCompleted(string $module, int $limit, int $offset, ?int $workflowId = null): array;

    public function getDailyCompletedCounts(string $module, Carbon $from, Carbon $to, ?int $workflowId = null): Collection;

    public function getCompletedWorkflows(string $module): Collection;
}
