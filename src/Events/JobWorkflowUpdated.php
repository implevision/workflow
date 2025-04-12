<?php

namespace Taurus\Workflow\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class JobWorkflowUpdated
{
    use Dispatchable, SerializesModels;

    public int $jobWorkflowId;
    public array $payload;

    /**
     * Create a new event instance.
     */
    public function __construct(int $jobWorkflowId, array $payload)
    {
        $this->jobWorkflowId = $jobWorkflowId;
        $this->payload = $payload;
    }
}
