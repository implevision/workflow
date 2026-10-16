<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Taurus\Workflow\Services\WorkflowActions\Helpers\Email\PrepareEmailData;
use Taurus\Workflow\Services\WorkflowEmailService;
use Taurus\Workflow\Services\WorkflowService;

class EmailAction extends AbstractWorkflowAction
{
    protected $emailInformation = [];

    public function handle()
    {
        $payload = $this->getPayload();
        if (empty($payload['id'])) {
            throw new \Exception('Email template ID is required.');
        }

        // Use the edited template payload directly if provided (manual workflow execution).
        if (! empty($payload['editedTemplatePayload'])) {
            $this->loadEditedTemplate($payload['editedTemplatePayload']);

            return;
        }

        $this->loadTemplateById($payload['id']);
    }

    private function loadEditedTemplate(array $editedPayload): void
    {
        $response = WorkflowEmailService::extractPlaceholders($editedPayload);

        if (empty($response) || empty($response['data']) || ! $response['status']) {
            throw new \Exception('Error extracting placeholders from the template.');
        }

        $editedPayload['extractedPlaceholders'] = $response['data']['extractedPlaceholders'] ?? [];

        $this->emailInformation = $editedPayload;
    }

    private function loadTemplateById(string $id): void
    {
        $response = WorkflowEmailService::getEmailInformation($id);

        if (empty($response) || empty($response['data']) || ! $response['status']) {
            throw new \Exception('No email template found for the given ID.');
        }

        $this->emailInformation = [...$response['data'], ...$this->extendedTemplateInfo];
    }

    public function getListOfRequiredData()
    {
        $extractedPlaceHolder = ! empty($this->emailInformation['extractedPlaceholders']) ? $this->emailInformation['extractedPlaceholders'] : [];
        preg_match_all('/{{\s*(.*?)\s*}}/', $this->emailInformation['subject'], $subjectPlaceholderMatches);
        preg_match_all('/{{\s*(.*?)\s*}}/', $this->emailInformation['senderName'], $senderPlaceholderMatches);

        return [...$extractedPlaceHolder, ...$subjectPlaceholderMatches[1], ...$senderPlaceholderMatches[1]];
    }

    /**
     * Whether recipients who unsubscribed must be skipped for this template: it is
     * flagged as promotional (`isTransactionalEmail`, shown as "Allow users to
     * unsubscribe from promotional emails") or it carries the UnsubscribeURL placeholder.
     * Any other email is transactional and goes to every recipient.
     */
    public function isPromotionalEmail(): bool
    {
        return ! empty($this->emailInformation['isTransactionalEmail'])
            || in_array('UnsubscribeURL', $this->getListOfRequiredData(), true);
    }

    public function getListOfMandateData()
    {
        $payload = $this->getPayload();

        return ! empty($payload['mandatoryPlaceholders']) ? $payload['mandatoryPlaceholders'] : [];
    }

    public function execute()
    {
        $workflowId = $this->getWorkflowId();
        $jobWorkflowId = $this->getJobWorkflowId();
        $recordIdentifier = $this->getRecordIdentifier();
        $feedFile = $this->getFeedFile();
        $data = $this->getData();
        $payload = $this->removeUnsubscribedCopyRecipients($this->getPayload());

        try {
            \Log::info('WORKFLOW - Preparing email data');
            $prepareEmailData = new PrepareEmailData;
            $prepareEmailData->prepare($workflowId, $jobWorkflowId, $recordIdentifier, $payload['id'], [
                'csvFile' => $feedFile,
                'data' => $data,
                'postAction' => ! empty($payload['postAction']) ? $payload['postAction'] : '',
                'actionPayload' => $payload ?? [],
            ], $this->emailInformation)->execute();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Drops unsubscribed addresses from the action's CC/BCC lists for promotional
     * emails, the same way the To recipients are filtered in
     * AbstractDispatchService::validateAndFilterData().
     */
    private function removeUnsubscribedCopyRecipients(array $payload): array
    {
        if (! $this->isPromotionalEmail()) {
            return $payload;
        }

        $workflowService = app(WorkflowService::class);

        foreach (['cc', 'bcc'] as $field) {
            if (empty($payload[$field])) {
                continue;
            }

            $emails = array_values(array_filter(array_map('trim', explode(',', $payload[$field]))));
            $payload[$field] = implode(',', $workflowService->applyEmailSupressedByConsumerFilter($emails, true));
        }

        return $payload;
    }
}
