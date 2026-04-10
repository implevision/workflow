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
        try {
            $response = WorkflowEmailService::getEmailInformation($payload['id']);

            if (empty($response) || empty($response['data']) || ! $response['status']) {
                throw new \Exception('No template found for the given ID.');
            }

            $this->templateInformation = $response['data'];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getListOfRequiredData()
    {
        $extractedPlaceHolder = ! empty($this->templateInformation['extractedPlaceholders']) ? $this->templateInformation['extractedPlaceholders'] : [];
        preg_match_all('/{{\s*(.*?)\s*}}/', $this->templateInformation['subject'] ?? '', $subjectPlaceholderMatches);

        return [...$extractedPlaceHolder, ...$subjectPlaceholderMatches[1]];
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
                $response = $printAsPdf->generate($jobWorkflowId, $data, $this->templateInformation);
                break;
            default:
                throw new \Exception("Unsupported output action type: {$outputActionType}");
        }
    }
}
