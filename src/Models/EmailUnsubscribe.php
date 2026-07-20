<?php

namespace Taurus\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class EmailUnsubscribe extends Model
{
    protected $table = 'email_unsubscribes';

    protected $fillable = [
        'email',
        'campaign_type',
        'unsubscribed_at',
        'reason',
        'source',
        'metadata',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
