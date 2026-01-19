<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowLog extends Model
{
    protected $table = 'tbl_workflow_logs';

    protected $fillable = [
        'workflow_id',
        'record_identifier',
        'module',
        'status',
        'job_workflow_id',  
    ];

    protected $casts = [
        'workflow_id'       => 'integer',
        'record_identifier' => 'integer',
    ];

    /**
     * Workflow execution status constants.
     */
    public const STATUS_IN_PROGRESS = 'IN PROGRESS';
    public const STATUS_COMPLETED   = 'COMPLETED';

    /**
     * Mark workflow log entry as COMPLETED.
     *
     * @param int $workflowId
     * @param int $jobWorkflowId
     * @return void
     */
    public static function markWorkflowCompleted(int $workflowId, int $jobWorkflowId): void
    {
        self::where('workflow_id', $workflowId)
            ->where('job_workflow_id', $jobWorkflowId)
            ->update(['status' => self::STATUS_COMPLETED]);
    }
}