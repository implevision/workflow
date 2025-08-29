<?php

namespace Taurus\Workflow\Services\WorkflowActions\Helpers\Email;

class PrepareEmailData
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
        $senderName = ! empty($this->emailInformation['senderName']) ? $this->emailInformation['senderName'] : getTenant();
        $senderEmailAddress = str_replace('{{tenant}}', tenant(), config('workflow.sender_email_address'));
        $from = sprintf('"%s" <%s>', $senderName, $senderEmailAddress);
        $actionPayload = [
            'workflowId' => $this->workflowId,
            'jobWorkflowId' => $this->jobWorkflowId,
            'recordIdentifier' => $this->recordIdentifier,
            'actionType' => 'Email',
            'csvFile' => $this->payload['csvFile'] ?? null,
            'data' => $this->payload['data'] ?? [],
            'emailTemplate' => $this->emailInformation['html'],
            'plainEmailTemplate' => '',
            'subject' => $this->emailInformation['subject'],
            'payload' => [],
            'from' => $from,
            'replyTo' => ! empty($this->emailInformation['replyTo']) ? explode(',', $this->emailInformation['replyTo']) : [],
            'postAction' => $this->payload['postAction'] ?? '',
            'actionPayload' => $this->payload['actionPayload'] ?? [],
        ];

        try {
            $action = new EmailClient;
            $action->setPayload($actionPayload);
            $action->execute();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
