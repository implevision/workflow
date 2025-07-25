<?php

namespace Taurus\Workflow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Taurus\Workflow\Services\SES;
use Taurus\Workflow\Events\PostActionEvent;

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
        $this->onQueue('workflow-bulk-email');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobWorkflowId = !empty($this->payload['jobWorkflowId']) ? $this->payload['jobWorkflowId'] : 0;
        $workflowId = !empty($this->payload['workflowId']) ? $this->payload['workflowId'] : 0;
        $recordIdentifier = !empty($this->payload['recordIdentifier']) ? $this->payload['recordIdentifier'] : 0;
        $module = !empty($this->payload['module']) ? $this->payload['module'] : "";

        setRunningWorkflowId($workflowId);
        setRunningJobWorkflowId($jobWorkflowId);
        setModuleForCurrentWorkflow($module);
        setRecordIdentifierForRunningWorkflow($recordIdentifier);

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
        $emailTemplate = $this->payload['emailTemplate'];
        $plainEmailTemplate = $this->payload['plainEmailTemplate'];
        $jobWorkflowId = !empty($this->payload['jobWorkflowId']) ? $this->payload['jobWorkflowId'] : 0;
        $workflowId = !empty($this->payload['workflowId']) ? $this->payload['workflowId'] : 0;
        $recordIdentifier = !empty($this->payload['recordIdentifier']) ? $this->payload['recordIdentifier'] : 0;
        $from = $this->payload['from'];
        $subject = $this->payload['subject'];
        $postAction = $this->payload['postAction'];
        $module = !empty($this->payload['module']) ? $this->payload['module'] : "";

        //SEND EMAIL
        try {
            \Log::info('WORKFLOW - Creating SES Bulk Request');
            //$messageId = SES::sendEmail($from, $subject, $emailTemplate, $this->payload['payload'], $plainEmailTemplate, $jobWorkflowId);
            $messageId = 99;
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error creating SES Bulk Request: ' . $e->getMessage());
            throw $e; // Re-throw the exception to be handled by the queue system
        }

        //UPDATE LOG TABLE

        //EVENT
        try {
            $this->payload['documentId'] = 23;
            $this->payload['documentName'] = "FNOL Email";
            event(new PostActionEvent($module, $this->payload, $messageId));
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error executing post action: ' . $e->getMessage());
        }
    }
}
