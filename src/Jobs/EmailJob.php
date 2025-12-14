<?php

namespace Taurus\Workflow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Taurus\Workflow\Events\PostActionEvent;
use Taurus\Workflow\Services\AWS\SES;

class EmailJob implements ShouldQueue
{
    use Queueable;

    private $emailClient;

    private $payload;

    private $actionPayload;

    /**
     * Create a new job instance.
     */
    public function __construct($emailClient = 'SES_EMAIL', $payload = [], $actionPayload = [])
    {
        $this->emailClient = $emailClient;
        $this->payload = $payload;
        $this->actionPayload = $actionPayload;
        $queue = config('workflow.email_queue');
        $defaultQueue = getDefaultQueue();
        $this->onQueue($queue ?? $defaultQueue);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobWorkflowId = ! empty($this->payload['jobWorkflowId']) ? $this->payload['jobWorkflowId'] : 0;
        $workflowId = ! empty($this->payload['workflowId']) ? $this->payload['workflowId'] : 0;
        $recordIdentifier = ! empty($this->payload['recordIdentifier']) ? $this->payload['recordIdentifier'] : 0;
        $module = ! empty($this->payload['module']) ? $this->payload['module'] : '';

        setRunningWorkflowId($workflowId);
        setRunningJobWorkflowId($jobWorkflowId);
        setModuleForCurrentWorkflow($module);
        setRecordIdentifierForRunningWorkflow($recordIdentifier);

        switch ($this->emailClient) {
            case 'SES_EMAIL':
                $this->createSESRequest();
                break;
            default:
                break;
        }
    }

    public function createSESRequest()
    {
        $emailTemplate = $this->payload['emailTemplate'];
        $plainEmailTemplate = $this->payload['plainEmailTemplate'];
        $jobWorkflowId = ! empty($this->payload['jobWorkflowId']) ? $this->payload['jobWorkflowId'] : 0;
        $workflowId = ! empty($this->payload['workflowId']) ? $this->payload['workflowId'] : 0;
        $recordIdentifier = ! empty($this->payload['recordIdentifier']) ? $this->payload['recordIdentifier'] : 0;
        $from = $this->payload['from'];
        $subject = $this->payload['subject'];
        $postAction = $this->payload['postAction'];
        $module = ! empty($this->payload['module']) ? $this->payload['module'] : '';
        $replyTo = ! empty($this->payload['replyTo']) ? $this->payload['replyTo'] : '';

        // SEND EMAIL
        $messageId = 0;
        try {
            \Log::info('WORKFLOW - Creating SES Request');
            $messageId = SES::createRequest($from, $subject, $emailTemplate, $this->payload['payload'], $plainEmailTemplate, $jobWorkflowId, $replyTo);
            \Log::info('WORKFLOW - SES Request created with Message ID: '.$messageId);
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error creating SES Request: '.$e->getMessage());
            throw $e; // Re-throw the exception to be handled by the queue system
        }

        // UPDATE LOG TABLE

        // EVENT
        try {
            if ($messageId && $postAction) {
                $this->payload['actionPayload'] = $this->actionPayload;
                \Log::info('WORKFLOW - Executing post action for SES Request');
                event(new PostActionEvent($module, $this->payload, $messageId));
            }
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error executing post action: '.$e->getMessage());
        }
    }
}
