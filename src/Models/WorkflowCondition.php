<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowCondition extends Model
{
    use SoftDeletes;

    protected $table;

    protected $fillable = ['tenant_id','workflow_id', 'conditions', 'notes', 'status'];

    protected $casts = [
        'conditions' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = getTablePrefix();
        $this->table = $prefix.'_workflow_conditions';
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public function actions()
    {
        return $this->hasMany(WorkflowAction::class, 'condition_id');
    }
}
