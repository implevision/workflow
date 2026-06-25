<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Taurus\Workflow\Services\WorkflowActions\Helpers\Email\PrepareEmailData;
use Taurus\Workflow\Services\WorkflowEmailService;

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

    private function loadTemplateById(int $id): void
    {
        $response = WorkflowEmailService::getEmailInformation($id);

        if (empty($response) || empty($response['data']) || ! $response['status']) {
            throw new \Exception('No email template found for the given ID.');
        }

        $this->emailInformation = $response['data'];
    }

    public function getListOfRequiredData()
    {
        $extractedPlaceHolder = ! empty($this->emailInformation['extractedPlaceholders']) ? $this->emailInformation['extractedPlaceholders'] : [];
        preg_match_all('/{{\s*(.*?)\s*}}/', $this->emailInformation['subject'], $subjectPlaceholderMatches);
        preg_match_all('/{{\s*(.*?)\s*}}/', $this->emailInformation['senderName'], $senderPlaceholderMatches);

        return [...$extractedPlaceHolder, ...$subjectPlaceholderMatches[1], ...$senderPlaceholderMatches[1]];
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
        $payload = $this->getPayload();

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
}
