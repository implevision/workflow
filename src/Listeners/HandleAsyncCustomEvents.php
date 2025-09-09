<?php

namespace Taurus\Workflow\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Taurus\Workflow\Events\AsyncCustomEvents;
use Taurus\Workflow\Jobs\InvokeMatchingWorkflowJob;

class HandleAsyncCustomEvents implements ShouldQueue
{
    public function handle(AsyncCustomEvents $event): int
    {
        $recordIdentifier = $event->data['recordIdentifier'] ?? 0;
        $data = $event->data;
        unset($data['recordIdentifier']);
        InvokeMatchingWorkflowJob::dispatch($recordIdentifier, $event->event, $event->eventVirtualModel, $data);

        return true;
    }
}
