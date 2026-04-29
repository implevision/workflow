<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class EmailDeliveryEvent extends Model
{
    protected $fillable = [
        'message_id',
        'event_type',
        'workflow_id',
        'event_timestamp',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'event_timestamp' => 'datetime',
        'workflow_id' => 'integer',
    ];

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_ERROR = 'ERROR';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $prefix = getTablePrefix();
        $this->table = $prefix.'_email_delivery_events';
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'workflow_id', 'id');
    }
}
