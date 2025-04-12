<?php

namespace Taurus\Workflow\Listeners;

use Illuminate\Support\Facades\Log;
use Taurus\Workflow\Events\JobWorkflowUpdated;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;

class HandleJobWorkflowUpdate
{
    /**
     * Handle the event.
     */
    public function handle(JobWorkflowUpdated $event): void
    {
        $jobWorkflowRepo = app(JobWorkflowRepository::class);
        try {
            $jobWorkflowInfo = $jobWorkflowRepo->getInfo($event->jobWorkflowId);
            $countOfProcessedRecord = $jobWorkflowInfo['total_no_of_records_executed'] + count($event->payload);
            $status = $countOfProcessedRecord == $jobWorkflowInfo['total_no_of_records_to_execute'] ? 'COMPLETED' : $jobWorkflowInfo['status'];
            $jobWorkflow = [
                'total_no_of_records_executed' => $countOfProcessedRecord,
                'status' => $status
            ];
            $jobWorkflowRepo->updateData($event->jobWorkflowId, $jobWorkflow);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }
}
