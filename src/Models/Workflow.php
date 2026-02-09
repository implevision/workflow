<?php

namespace Taurus\Workflow\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use SoftDeletes;

    protected $table;

    protected $fillable = [
        'module',
        'name',
        'description',
        'effective_action_to_execute_workflow',
        'record_action_to_execute_workflow',
        'date_time_info_to_execute_workflow',
        'custom_date_time_info_to_execute_workflow',
        'odyssey_action_to_execute_workflow',
        'workflow_execution_frequency',
        'is_active',
        'aws_event_bridge_arn',
    ];

    protected $casts = [
        'custom_date_time_info_to_execute_workflow' => 'json',
        'date_time_info_to_execute_workflow' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = getTablePrefix();
        $this->table = $prefix.'_workflows';
    }

    public function conditions()
    {
        return $this->hasMany(WorkflowCondition::class, 'workflow_id');
    }

    public function getNextExecution(string $frequency): string
    {
        return match (strtoupper(trim($frequency))) {
            'ONCE' => Carbon::now()->modify('next day')->format('Y-m-d'),
            'MONTH' => Carbon::now()->modify('next Month')->startOfMonth()->format('Y-m-d'),
            'YEAR' => Carbon::now()->modify('first day of January next year')->format('Y-m-d'),
            'WEEK' => Carbon::now()->modify('next Monday')->format('Y-m-d '),
            default => throw new \InvalidArgumentException("Unknown schedule type: {$frequency}"),
        };
    }

    /**
     * Scope to retrieve only active workflows.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function actions()
    {
        return $this->hasManyThrough(
            WorkflowAction::class,
            WorkflowCondition::class,
            'workflow_id',   // FK on workflow_conditions table
            'condition_id',  // FK on workflow_actions table
            'id',            // PK on workflows table
            'id'             // PK on workflow_conditions table
        );
    }
}
