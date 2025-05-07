<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowCondition extends Model
{
    use SoftDeletes;
    protected $table;

    protected $fillable = ['workflow_id', 'conditions'];

    protected $casts = [
        'conditions' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = config('workflow.table_prefix', 'tb_taurus');
        $this->table = $prefix . '_workflow_conditions';
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
