<?php

namespace Taurus\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomEvents
{
    use Dispatchable, SerializesModels;

    public string $event;

    public string $eventVirtualModel;

    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(string $event, string $eventVirtualModel, array $data)
    {
        $this->event = $event;
        $this->eventVirtualModel = $eventVirtualModel;
        $this->data = $data;
    }
}
