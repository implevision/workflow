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
            $jobWorkflow = $event->jobWorkflowData;
            if (array_key_exists('total_no_of_records_executed', $event->jobWorkflowData)) {
                $jobWorkflowInfo = $jobWorkflowRepo->getInfo($event->jobWorkflowId);
                $countOfProcessedRecord = $jobWorkflowInfo['total_no_of_records_executed'] + $event->jobWorkflowData['total_no_of_records_executed'];
                $status = $countOfProcessedRecord == $jobWorkflowInfo['total_no_of_records_to_execute'] ? 'COMPLETED' : $jobWorkflowInfo['status'];
                $jobWorkflow = [
                    'total_no_of_records_executed' => $countOfProcessedRecord,
                    'status' => $status
                ];
            }
            $jobWorkflowRepo->updateData($event->jobWorkflowId, $jobWorkflow);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }
}
