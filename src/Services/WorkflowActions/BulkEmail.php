<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Taurus\Workflow\Events\JobWorkflowUpdated;
use Taurus\Workflow\Jobs\BulkEmailJob;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;

class BulkEmail
{
    public $payload;

    public $emailClient = null;
    public function __construct($emailClient = 'SES_BULK_EMAIL')
    {
        $this->emailClient = $emailClient;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    public function execute()
    {
        $this->sendEmails();
    }

    private function sendEmails()
    {
        $jobPayload = $this->payload;
        $csvFile = $jobPayload['csvFile'];

        //$workflowId = !empty($jobPayload['workflowId']) ? $jobPayload['workflowId'] : 0;
        $jobWorkflowId = !empty($jobPayload['jobWorkflowId']) ? $jobPayload['jobWorkflowId'] : 0;

        $rowCount = 0;
        $placeholder = [];
        if (file_exists($csvFile)) {
            if (($handle = fopen($csvFile, "r")) !== false) {
                while (!feof($handle)) {
                    $data = fgetcsv($handle);
                    if (!$data || !is_array($data)) {
                        continue;
                    }

                    if ($rowCount == 0) {
                        $placeholder = $data;
                        $rowCount++;
                        continue;
                    }

                    if (count($placeholder) != count($data)) {
                        \Log::error('CSV file has different number of columns in row ' . $rowCount);
                        continue;
                    }

                    $data = array_combine($placeholder, $data);

                    $leftover = ($rowCount - 1) / 50;
                    $leftover = $leftover - floor($leftover);

                    if (($rowCount - 1) && $leftover == 0) {
                        BulkEmailJob::dispatch($this->emailClient, $jobPayload);
                        $jobPayload['payload'] = [];
                    }
                    $jobPayload['payload'][] = $data;
                    $rowCount++;
                }
            }
        }

        if (count($jobPayload['payload'])) {
            BulkEmailJob::dispatch($this->emailClient, $jobPayload);
        }

        //Update count of workflow
        if ($jobWorkflowId) {
            event(new JobWorkflowUpdated($jobWorkflowId, [
                'total_no_of_records_to_execute' => $rowCount - 1
            ]));
        }
    }
}
