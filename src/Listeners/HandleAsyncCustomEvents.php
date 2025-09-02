<?php

namespace Taurus\Workflow\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Taurus\Workflow\Events\AsyncCustomEvents;
use Taurus\Workflow\Jobs\InvokeMatchingWorkflowJob;

class HandleAsyncCustomEvents implements ShouldQueue
{
    public function handle(AsyncCustomEvents $event): int
    {
        InvokeMatchingWorkflowJob::dispatch($event->data['recordIdentifier'], $event->event, $event->eventVirtualModel, $event->data);

        return true;
    }
}
