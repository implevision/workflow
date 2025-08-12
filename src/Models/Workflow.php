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
        'workflow_execution_frequency',
        'workflow_next_date_to_execute',
        'is_active',
        'aws_event_bridge_arn',
    ];

    protected $casts = [
        'date_time_info_to_execute_workflow' => 'json',
        'workflow_next_date_to_execute' => 'datetime',
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

    public function calculateAndUpdateNextExecution(): string
    {
        $next = null;
        if ($this->date_time_info_to_execute_workflow['recurringFrequency']) {
            $frequency = $this->date_time_info_to_execute_workflow['recurringFrequency'];
            $next = $this->getNextExecution($frequency);
        } elseif ($this->date_time_info_to_execute_workflow['executionEffectiveDate']) {
            $next = $this->date_time_info_to_execute_workflow['executionEffectiveDate'];
        }

        $this->update(['workflow_next_date_to_execute' => $next]);

        return $next;
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
}
