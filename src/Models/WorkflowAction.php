<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowAction extends Model
{
    use SoftDeletes;
    protected $table;

    protected $fillable = [
        'condition_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = config('workflow.table_prefix', 'tb_taurus');
        $this->table = $prefix . '_workflow_actions';
    }

    public function condition()
    {
        return $this->belongsTo(WorkflowCondition::class, 'condition_id');
    }
}
