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
     * Extract all payload keys that start with "attach" (case-insensitive) and
     * return them as an array.
     *
     * A key is present here whenever an Attach* placeholder was requested, even
     * when its callback legitimately found nothing to attach and returned an
     * empty list -- e.g. generateClaimAssignmentForm() when the record has no
     * matching form. array_pop() on that empty list returns null, and null was
     * previously kept as a real entry (array_values() only reindexes, it does
     * not drop it), so SES::processAttachment() received a [null] list and
     * crashed on $file['path']. Skipping a null result here is what makes an
     * empty attachment mean "no attachment" instead of "one broken attachment".
     */
    public function extractAttachments(array $payload): array
    {
        $attachments = [];

        foreach ($payload as $key => $value) {
            // Case-insensitive check for keys starting with "attach"
            if (! preg_match('/^attach/i', $key)) {
                continue;
            }

            $popped = array_pop($value);

            if ($popped !== null) {
                $attachments[$key] = $popped;
            }
        }

        return array_values($attachments);
    }
}
