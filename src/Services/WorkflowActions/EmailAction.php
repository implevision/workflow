<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Taurus\Workflow\Services\WorkflowEmailService;
use Taurus\Workflow\Services\WorkflowActions\PrepareBulkEmailData;

class EmailAction extends AbstractWorkflowAction
{

    protected $emailInformation = [];

    public function handle()
    {
        $payload = $this->getPayload();

        try {
            /*$emailInformation = [];
            $emailInformation['html'] = "<h1>test</h1>";
            $emailInformation['subject'] = "hello";
            $emailInformation['placeHolder'] = ['insuredName', 'PolicyNumber', 'insuredMailingAddress'];
            $emailInformation['mandatoryFields'] = ['insuredName', 'PolicyNumber', 'insuredMailingAddress'];

            $this->emailInformation = $emailInformation;*/
            //$this->emailInformation = WorkflowEmailService::getEmailInformation($payload['id']);
        } catch (\Exception $e) {
            \Log::error('Error fetching email information: ' . $e->getMessage());
        }
    }
    public function getRequiredData()
    {
        return !empty($this->emailInformation['placeHolder']) ? $this->emailInformation['placeHolder'] : [];
    }

    public function execute()
    {
        $workflowId = $this->getWorkflowId();
        $jobWorkflowId = $this->getJobWorkflowId();
        $feedFile = $this->getFeedFile();
        $data = $this->getData();
        $payload = $this->getPayload();

        new PrepareBulkEmailData()
            ->prepare($workflowId, $jobWorkflowId, $payload['id'], [
                'csvFile' => $feedFile,
                'data' => $data
            ], $this->emailInformation)->execute();
    }
}
