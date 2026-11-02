<?php

namespace Taurus\Workflow\Repositories\Eloquent;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
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

    public function findForJob(?int $workflowId, ?int $jobWorkflowId): ?WorkflowLog
    {
        return $this->model->where('workflow_id', $workflowId)
            ->where('job_workflow_id', $jobWorkflowId)
            ->first();
    }

    public function getCompleted(string $module, int $limit, int $offset, ?int $workflowId = null): array
    {
        $query = $this->model->with('workflow:id,name')
            ->with('user:Admin_ID,s_ScreenName')
            ->where('module', $module)
            ->where('status', WorkflowLog::STATUS_COMPLETED)
            ->when($workflowId !== null, fn ($q) => $q->where('workflow_id', $workflowId))
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $data = $query->limit($limit)->offset($offset)->get();

        return ['data' => $data, 'total' => $total];
    }

    public function getDailyCompletedCounts(string $module, CarbonInterface $from, CarbonInterface $to, ?int $workflowId = null): Collection
    {
        return $this->model->where('module', $module)
            ->where('status', WorkflowLog::STATUS_COMPLETED)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->when($workflowId !== null, fn ($q) => $q->where('workflow_id', $workflowId))
            ->selectRaw('DATE(created_at) as log_date, COUNT(*) as log_count')
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get()
            ->keyBy('log_date');
    }

    public function getCompletedWorkflows(string $module): Collection
    {
        return $this->model->with('workflow:id,name')
            ->where('module', $module)
            ->where('status', WorkflowLog::STATUS_COMPLETED)
            ->select('workflow_id')
            ->distinct()
            ->get();
    }
}
