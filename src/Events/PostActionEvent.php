<?php

namespace Taurus\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostActionEvent
{
    use Dispatchable, SerializesModels;

    public array $payload;

    public string $module;

    public string $messageId;

    /**
     * Create a new event instance.
     */
    public function __construct($module, $payload, $messageId)
    {
        $this->payload = $payload;
        $this->module = $module;
        $this->messageId = $messageId;
    }
}
