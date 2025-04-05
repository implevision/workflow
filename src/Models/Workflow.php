<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_workflows';

    protected $fillable = [
        'module',
        'name',
        'description',
        'effective_action_to_execute_workflow',
        'record_action_to_execute_workflow',
        'date_time_info_to_execute_workflow',
        'workflow_execution_frequency'
    ];

    protected $casts = [
        'date_time_info_to_execute_workflow' => 'json',
    ];

    public function conditions()
    {
        return $this->hasMany(WorkflowCondition::class, 'workflow_id');
    }
}
