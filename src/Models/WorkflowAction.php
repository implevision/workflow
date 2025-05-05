<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowAction extends Model
{
    use SoftDeletes;

    protected $tablePrefix = config('workflow.table_prefix', 'tb_taurus');
    protected $table = $tablePrefix . '_workflow_actions';

    protected $fillable = [
        'condition_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'json',
    ];

    public function condition()
    {
        return $this->belongsTo(WorkflowCondition::class, 'condition_id');
    }
}
