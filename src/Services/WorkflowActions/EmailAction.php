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
        try {
            $response = WorkflowEmailService::getEmailInformation($payload['id']);

            if (empty($response) || empty($response['data']) || ! $response['status']) {
                throw new \Exception('No email template found for the given ID.');
            }

            $this->emailInformation = $response['data'];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getListOfRequiredData()
    {
        return ! empty($this->emailInformation['extractedPlaceholders']) ? $this->emailInformation['extractedPlaceholders'] : [];
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
