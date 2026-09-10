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
     * return them as an array, honouring the template's attachment selection.
     *
     * On the GraphQL path a key only reaches the payload if it was ticked on the
     * template -- the selection becomes a required placeholder, so unselected
     * attachments are never fetched. On the entity-payload path the producer fills
     * the payload directly and the placeholder list is never consulted, so without
     * this filter every attach* key would be sent regardless of the selection.
     *
     * A null selection means the template has no attachments field at all (older
     * templates), in which case nothing is filtered and behaviour is unchanged.
     */
    public function extractAttachments(array $payload): array
    {
        $selected = $this->payload['selectedAttachments'] ?? null;
        $selected = is_array($selected) ? array_map('strtolower', $selected) : null;

        $attachments = [];

        foreach ($payload as $key => $value) {
            // Case-insensitive check for keys starting with "attach"
            if (! preg_match('/^attach/i', $key)) {
                continue;
            }

            if ($selected !== null && ! in_array(strtolower($key), $selected, true)) {
                \Log::info('WORKFLOW - Attachment not selected on the template, skipping: '.$key);

                continue;
            }

            $attachments[$key] = array_pop($value);
        }

        return array_values($attachments);
    }
}
