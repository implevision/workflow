<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Taurus\Workflow\Services\WorkflowActions\Helpers\WorkflowOutput\PrepareWorkflowOutputData;
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

        return $extractedPlaceHolder;
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
        $data = $this->getData();
        $payload = $this->getPayload();

        try {
            \Log::info('WORKFLOW - Preparing workflow output data');
            $prepareWorkflowOutputData = new PrepareWorkflowOutputData;
            $prepareWorkflowOutputData->prepare($workflowId, $jobWorkflowId, $recordIdentifier, $payload['id'], [
                'data' => $data,
                'postAction' => ! empty($payload['postAction']) ? $payload['postAction'] : '',
                'actionPayload' => $payload ?? [],
            ], $this->templateInformation)->execute();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
