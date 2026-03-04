<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowConfig extends Model
{
    protected $table;

    protected $fillable = [
        'config_key',
        'config_value',
        'last_checked',
    ];

    protected $casts = [
        'last_checked' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = getTablePrefix();
        $this->table = $prefix.'_workflow_config';
    }
}
