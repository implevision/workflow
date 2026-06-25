<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Taurus\Workflow\Services\WorkflowActions\Helpers\WorkflowOutput\PrintAsPdf;
use Taurus\Workflow\Services\WorkflowEmailService;

class WorkflowOutputAction extends AbstractWorkflowAction
{
    protected $templateInformation = [];

    public function handle()
    {
        $payload = $this->getPayload();
        if (empty($payload['id'])) {
            throw new \Exception('Template ID is required.');
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
        $response = WorkflowEmailService::extractPlaceholdersFromTemplate($editedPayload);

        if (empty($response) || empty($response['data']) || ! $response['status']) {
            throw new \Exception('Error extracting placeholders from the template.');
        }

        $editedPayload['extractedPlaceholders'] = $response['data']['extractedPlaceholders'] ?? [];

        $this->templateInformation = $editedPayload;
    }

    private function loadTemplateById(int $id): void
    {
        $response = WorkflowEmailService::getEmailInformation($id);

        if (empty($response) || empty($response['data']) || ! $response['status']) {
            throw new \Exception('No template found for the given ID.');
        }

        $this->templateInformation = $response['data'];
    }

    public function getListOfRequiredData()
    {
        $extractedPlaceHolder = ! empty($this->templateInformation['extractedPlaceholders']) ? $this->templateInformation['extractedPlaceholders'] : [];

        return $extractedPlaceHolder;
    }

    public function getListOfMandateData()
    {
        $payload = $this->getPayload();

        return ! empty($payload['mandatoryPlaceholders']) ? $payload['mandatoryPlaceholders'] : [];
    }

    public function execute()
    {
        $jobWorkflowId = $this->getJobWorkflowId();
        $data = $this->getData();
        $payload = $this->getPayload();

        $outputActionType = $payload['outputActionType'] ?? '';

        switch ($outputActionType) {
            case 'PRINT_AS_PDF':
                \Log::info('WORKFLOW - Generating PDF output');
                $printAsPdf = new PrintAsPdf;
                $printAsPdf->generate($jobWorkflowId, $data, $this->templateInformation);
                break;
            default:
                throw new \Exception("Unsupported output action type: {$outputActionType}");
        }
    }
}
