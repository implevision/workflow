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
        'is_active'
    ];

    protected $casts = [
        'date_time_info_to_execute_workflow' => 'json',
        'workflow_next_date_to_execute' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = config('workflow.table_prefix', 'tb_taurus');
        $this->table = $prefix . '_workflows';
    }

    public function conditions()
    {
        return $this->hasMany(WorkflowCondition::class, 'workflow_id');
    }

    public function calculateAndUpdateNextExecution(): Carbon
    {
        $lastRun = $this->workflow_next_date_to_execute ?? Carbon::now();
        $next = $this->getNextExecution($lastRun, $this->workflow_execution_frequency);

        $this->update(['workflow_next_date_to_execute' => $next]);

        return $next;
    }

    public function getNextExecution(Carbon $lastRun, string $frequency): Carbon
    {
        return match (strtoupper(trim($frequency))) {
            'ONCE'  => $lastRun->addDay(),
            'MONTH' => $lastRun->addMonth()->startOfMonth(),
            'YEAR'  => $lastRun->addYear()->startOfYear(),
            default => throw new \InvalidArgumentException("Unknown schedule type: {$frequency}"),
        };
    }
}
