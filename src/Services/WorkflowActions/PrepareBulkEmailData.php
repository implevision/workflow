<?php

namespace Taurus\Workflow\Services\WorkflowActions;

class PrepareBulkEmailData
{
    protected $workflowId;

    protected $jobWorkflowId;

    protected $recordIdentifier;

    protected $emailTemplateId;

    protected $payload;

    protected $emailInformation;

    public function prepare($workflowId, $jobWorkflowId, $recordIdentifier, $emailTemplateId, $payload, $emailInformation)
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowId = $jobWorkflowId;
        $this->recordIdentifier = $recordIdentifier;
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
            'recordIdentifier' => $this->recordIdentifier,
            'actionType' => 'BulkEmail',
            'csvFile' => $this->payload['csvFile'] ?? null,
            'data' => $this->payload['data'] ?? [],
            'emailTemplate' => $this->emailInformation['html'],
            'plainEmailTemplate' => '',
            'subject' => $this->emailInformation['subject'],
            'payload' => [],
            'from' => $from,
            'postAction' => $this->payload['postAction'] ?? '',
            'actionPayload' => $this->payload['actionPayload'] ?? [],
        ];

        try {
            $action = new BulkEmail;
            $action->setPayload($actionPayload);
            $action->execute();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
