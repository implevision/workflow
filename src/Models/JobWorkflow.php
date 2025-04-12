<?php

namespace Taurus\Workflow\Models;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class JobWorkflow extends Model
{
    use SerializesModels;
    protected $table = 'tbl_job_workflow';

    public const STATUS_CREATED = 'CREATED';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'workflow_id',
        'batch_id',
        'status',
        'total_no_of_records_to_execute',
        'total_no_of_records_executed',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public static function getAllowedStatuses(): array
    {
        return [
            self::STATUS_CREATED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED
        ];
    }

    /**
     * Get the next batch ID for the given workflow ID.
     *
     * @param int $workflowId
     * @return int
     */
    public static function getNextBatchId(int $workflowId): int
    {
        return (self::where('workflow_id', $workflowId)->max('batch_id') ?? 0) + 1;
    }
}
