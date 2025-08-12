<?php

namespace Taurus\Workflow\Listeners;

use Taurus\Workflow\Events\PostActionEvent;
use Taurus\Workflow\Services\WorkflowService;

class HandlePostActionEvent
{
    /**
     * Handle the event.
     */
    public function handle(PostActionEvent $event): void
    {
        $payload = $event->payload;
        $module = $event->module;
        $messageId = $event->messageId;

        try {
            setRunningWorkflowId($payload['workflowId']);
            setRunningJobWorkflowId($payload['recordIdentifier']);
            setRecordIdentifierForRunningWorkflow($payload['recordIdentifier']);
        } catch (\Throwable $e) {
            // Log the error or handle it as needed
            \Log::error('WORKFLOW - Error setting workflow context: '.$e->getMessage());

            return;
        }

        try {
            $workflowService = app(WorkflowService::class);
            $workflowService->getPostActionService()->execute($module, $payload, $messageId);
        } catch (\Throwable $e) {
            // Log the error or handle it as needed
            \Log::error('WORKFLOW - Error to execute post action '.$e->getMessage().$e->getFile().$e->getLine());

            return;
        }
    }
}
