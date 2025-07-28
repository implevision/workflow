<?php

namespace Taurus\Workflow\Listeners;

use Illuminate\Support\Facades\Log;
use Taurus\Workflow\Events\JobWorkflowCreatedEvent;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;

class HandleJobWorkflowCreatedEvent
{
    public function handle(JobWorkflowCreatedEvent $event): int
    {
        try {
            $jobWorkflow = [
                'workflow_id' => $event->workFlowId,
                'status' => 'CREATED',
                'total_no_of_records_to_execute' => $event->jobWorkflowData['total_no_of_records_to_execute'] ?? 0,
                'total_no_of_records_executed' => $event->jobWorkflowData['total_no_of_records_executed'] ?? 0,
                'response' => []
            ];

            $repo = app(JobWorkflowRepository::class);
            return $repo->createSingle($jobWorkflow);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return 0;
        }
    }
}
