<?php

namespace Taurus\Workflow\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class InvokeMatchingWorkflowEvent
{
    use Dispatchable, SerializesModels;

    public int $entity;

    public string $entityAction;

    public string $entityType;

    /**
     * Create a new event instance.
     */
    public function __construct(int $entity, string $entityAction, string $entityType)
    {
        $this->entity = $entity;
        $this->entityAction = $entityAction;
        $this->entityType = $entityType;
    }
}
