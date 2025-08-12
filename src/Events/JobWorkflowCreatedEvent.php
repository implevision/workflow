<?php

namespace Taurus\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobWorkflowCreatedEvent
{
    use Dispatchable, SerializesModels;

    public int $workFlowId;

    public array $jobWorkflowData;

    /**
     * Create a new event instance.
     */
    public function __construct(int $workFlowId, array $jobWorkflowData)
    {
        $this->workFlowId = $workFlowId;
        $this->jobWorkflowData = $jobWorkflowData;
    }
}
