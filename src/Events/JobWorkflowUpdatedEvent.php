<?php

namespace Taurus\Workflow\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class JobWorkflowUpdatedEvent
{
    use Dispatchable, SerializesModels;

    public int $jobWorkflowId;
    public array $jobWorkflowData;

    /**
     * Create a new event instance.
     */
    public function __construct(int $jobWorkflowId, array $jobWorkflowData)
    {
        $this->jobWorkflowId = $jobWorkflowId;
        $this->jobWorkflowData = $jobWorkflowData;
    }
}
