<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Taurus\Workflow\Services\WorkflowActions\BulkEmail;

class PrepareBulkEmailData
{
    protected $workflowId;

    protected $jobWorkflowId;

    protected $emailTemplateId;

    protected $payload;

    protected $emailInformation;

    public function prepare($workflowId, $jobWorkflowId, $emailTemplateId, $payload, $emailInformation)
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowId = $jobWorkflowId;
        $this->emailTemplateId = $emailTemplateId;
        $this->payload = $payload;
        $this->emailInformation = $emailInformation;

        return $this;
    }

    public function execute()
    {
        $from = sprintf('"%s" <%s>', getTenant(), config('workflow.sender_email_address'));
        $actionPayload = [
            'workflowId' => $this->workflowId,
            'jobWorkflowId' => $this->jobWorkflowId,
            'actionType' => 'BulkEmail',
            'csvFile' => $this->payload['csvFile'] ?? null,
            'data' => $this->payload['data'] ?? [],
            'emailTemplate' => $this->emailInformation['html'],
            'plainEmailTemplate' => "",
            'subject' => $this->emailInformation['subject'],
            'payload' => [],
            'from' => $from
        ];

        $action = new BulkEmail();
        $action->setPayload($actionPayload);
        $action->execute();
    }
}
