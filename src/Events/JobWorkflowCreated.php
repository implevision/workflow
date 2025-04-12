<?php

namespace Taurus\Workflow\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class JobWorkflowCreated
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
