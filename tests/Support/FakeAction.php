<?php

namespace Taurus\Workflow\Tests\Support;

use Taurus\Workflow\Services\WorkflowActions\AbstractWorkflowAction;

// Stands in for every action class; behaviour is driven by its payload (requiredData, mandatoryPlaceholders, failOnHandle, failOnExecute, result).
class FakeAction extends AbstractWorkflowAction
{
    public bool $executed = false;

    public function handle()
    {
        if (! empty($this->payload['failOnHandle'])) {
            throw new \Exception('handle failed');
        }
    }

    public function getListOfRequiredData()
    {
        return $this->payload['requiredData'] ?? ['FirstName', 'PolicyNumber'];
    }

    public function getListOfMandateData()
    {
        return $this->payload['mandatoryPlaceholders'] ?? [];
    }

    public function execute()
    {
        $this->executed = true;

        if (! empty($this->payload['failOnExecute'])) {
            throw new \Exception('execute failed');
        }

        return $this->payload['result'] ?? null;
    }
}
