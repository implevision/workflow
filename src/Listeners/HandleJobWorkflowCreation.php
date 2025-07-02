<?php

namespace Taurus\Workflow\Listeners;

use Illuminate\Support\Facades\Log;
use Taurus\Workflow\Events\JobWorkflowCreated;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;

class HandleJobWorkflowCreation
{
    public function handle(JobWorkflowCreated $event): int
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
