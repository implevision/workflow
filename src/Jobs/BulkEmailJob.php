<?php

namespace Taurus\Workflow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Taurus\Workflow\Services\SES;

class BulkEmailJob implements ShouldQueue
{
    use Queueable;

    private $emailClient;
    private $payload;

    /**
     * Create a new job instance.
     */
    public function __construct($emailClient = 'SES_BULK_EMAIL', $payload = [])
    {
        $this->emailClient = $emailClient;
        $this->payload = $payload;
        $this->onQueue('bulk-email');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        switch ($this->emailClient) {
            case 'SES_BULK_EMAIL':
                $this->createSESBulkRequest();
                break;
            default:
                break;
        }
    }

    public function createSESBulkRequest()
    {
        $emailTemplate = file_get_contents($this->payload['emailTemplate']);
        $plainEmailTemplate = file_get_contents($this->payload['plainEmailTemplate']);
        $jobWorkflowId = !empty($this->payload['jobWorkflowId']) ? $this->payload['jobWorkflowId'] : 0;
        $from = '"NFIP Direct" <noreply@odysseynext.com>';
        $subject = $this->payload['subject'];
        SES::sendEmail($from, $subject, $emailTemplate, $this->payload['payload'], $plainEmailTemplate, $jobWorkflowId);
        \Log::info('Creating SES Bulk Request');
    }
}
