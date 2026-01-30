<?php

namespace Taurus\Workflow\Services\WorkflowActions\Helpers\Email;

use Taurus\Workflow\Events\JobWorkflowUpdatedEvent;
use Taurus\Workflow\Jobs\EmailJob;

class EmailClient
{
    public $payload;

    public $emailClient = null;

    public $batchToCreate = 50;

    public function __construct($emailClient = 'SES_EMAIL')
    {
        $this->emailClient = $emailClient;
    }

    public function setPayload($payload)
    {
        $payload['module'] = getModuleForCurrentWorkflow();
        $this->payload = $payload;
    }

    public function execute()
    {
        try {
            $this->sendEmails();
        } catch (\Exception $e) {
            throw $e; // Re-throw the exception to be handled by the queue system
        }
    }

    private function dispatchEmail($payload)
    {
        \Log::info('WORKFLOW - Dispatching email job for '.$this->emailClient);
        EmailJob::dispatch($this->emailClient, $payload, $this->payload['actionPayload']);
    }

    private function sendEmails()
    {
        $jobPayload = $this->payload;
        $csvFile = $jobPayload['csvFile'] ?? null;
        $data = $jobPayload['data'] ?? [];

        $totalNoOfRecordsToExecute = 0;
        // $workflowId = !empty($jobPayload['workflowId']) ? $jobPayload['workflowId'] : 0;
        $jobWorkflowId = ! empty($jobPayload['jobWorkflowId']) ? $jobPayload['jobWorkflowId'] : 0;

        if ($csvFile) {
            $totalNoOfRecordsToExecute = $this->processCSVFile($csvFile, $jobPayload);
        }

        if (is_array($data) && count($data)) {
            $totalNoOfRecordsToExecute = $this->processData($data, $jobPayload);
        }

        // Update count of workflow
        if ($jobWorkflowId) {
            event(new JobWorkflowUpdatedEvent($jobWorkflowId, [
                'total_no_of_records_to_execute' => $totalNoOfRecordsToExecute,
            ]));
        }
    }

    public function processCSVFile($csvFile, $jobPayload)
    {
        $rowCount = 0;
        $placeholder = [];
        if (file_exists($csvFile)) {
            if (($handle = fopen($csvFile, 'r')) !== false) {
                while (! feof(stream: $handle)) {
                    $data = fgetcsv($handle);
                    if (! $data || ! is_array($data)) {
                        continue;
                    }

                    if ($rowCount == 0) {
                        $placeholder = $data;
                        $rowCount++;

                        continue;
                    }

                    if (count($placeholder) != count($data)) {
                        \Log::error('WORKFLOW - CSV file has different number of columns in row '.$rowCount);

                        continue;
                    }

                    $data = array_combine($placeholder, $data);

                    $leftover = ($rowCount - 1) / $this->batchToCreate;
                    $leftover = $leftover - floor($leftover);

                    if (($rowCount - 1) && $leftover == 0) {
                        $this->dispatchEmail($jobPayload);
                        $jobPayload['payload'] = [];
                    }
                    $data['attachments'] = [...$this->extractAttachments($data)];
                    $jobPayload['payload'][] = $data;
                    $rowCount++;
                }

                if (count($jobPayload['payload'])) {
                    $this->dispatchEmail($jobPayload);
                }
            }
        }

        return $rowCount - 1; // Return total number of records to execute
    }

    public function processData($jobData, $jobPayload)
    {
        $rowCount = 0;
        $leftover = $rowCount / $this->batchToCreate;
        $leftover = $leftover - floor($leftover);

        foreach ($jobData as $data) {
            if ($rowCount && $leftover == 0) {
                $this->dispatchEmail($jobPayload);
                $jobPayload['payload'] = [];
            }
            $data['attachments'] = [...$this->extractAttachments($data)];
            $jobPayload['payload'][] = $data;
            $rowCount++;
        }

        if (count($jobPayload['payload'])) {
            $this->dispatchEmail($jobPayload);
        }

        return count($jobData);
    }

    /**
     * Extract all payload keys that start with "attachment" (case-insensitive)
     * and return them as an array.
     */
    public function extractAttachments(array $payload): array
    {
        $attachments = [];

        foreach ($payload as $key => $value) {
            // Case-insensitive check for keys starting with "attachment"
            if (preg_match('/^attach/i', $key)) {
                $attachments[$key] = array_pop($value);
            }
        }

        return array_values($attachments);
    }
}
