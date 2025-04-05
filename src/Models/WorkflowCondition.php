<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowCondition extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_workflow_conditions';

    protected $fillable = ['workflow_id', 'conditions'];

    protected $casts = [
        'conditions' => 'json',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public function actions()
    {
        return $this->hasMany(WorkflowAction::class, 'condition_id');
    }
}
