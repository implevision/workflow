<?php

namespace Taurus\Workflow\Listeners;

use Taurus\Workflow\Events\InvokeMatchingWorkflowEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class HandleInvokeMatchingWorkflowEvent
{
    /**
     * Handle the event.
     */
    public function handle(InvokeMatchingWorkflowEvent $event): void
    {
        try {
            // Log the event details
            Log::info('WORKFLOW - Handling job workflow created event', [
                'entity' => $event->entity,
                'action' => $event->entityAction,
                'type' => $event->entityType,
            ]);

            $command = getCommandToDispatchMatchingWorkflow($event->entity, $event->entityAction, $event->entityType);
            try {
                Artisan::call($command['command'], $command['options']);
            } catch (\Exception $e) {
                Log::info('WORKFLOW - Error dispatching matching workflow: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error("WORKFLOW - " . $e->getMessage());
        }
    }
}
