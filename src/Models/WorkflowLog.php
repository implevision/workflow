<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int|null $workflow_id
 * @property int|null $record_identifier
 * @property string|null $module
 * @property string|null $status
 * @property int|null $job_workflow_id
 * @property string|null $action_type
 * @property int|null $action_track_id
 * @property string|null $error
 * @property \Carbon\Carbon|null $created_at
 */
class WorkflowLog extends Model
{
    // tenant_id is for Nova, it is ignored for odyssey
    protected $fillable = [
        'tenant_id',
        'workflow_id',
        'record_identifier',
        'module',
        'status',
        'job_workflow_id',
        'action_type',
        'action_track_id',
        'error',
    ];

    protected $casts = [
        'workflow_id' => 'integer',
        'record_identifier' => 'integer',
    ];

    /**
     * Workflow execution status constants.
     */
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_ERROR = 'ERROR';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = getTablePrefix();
        $this->table = $prefix.'_workflow_logs';
    }

    /**
     * Mark workflow log entry as COMPLETED.
     */
    public static function markWorkflowCompleted(int $workflowId, int $jobWorkflowId): void
    {
        self::where('workflow_id', $workflowId)
            ->where('job_workflow_id', $jobWorkflowId)
            ->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Get the workflow associated with this workflow log.
     *
     * @return BelongsTo
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'workflow_id', 'id');
    }
}
