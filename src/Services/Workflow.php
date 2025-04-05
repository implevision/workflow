<?php

namespace Taurus\Workflow\Services;

use Taurus\Workflow\Services\WorkflowActions\BulkEmail;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;

class Workflow
{
    private $workflowId;

    private $workflowInfo = null;

    protected $jobWorkflowRepo;

    public function __construct(int $workflowId)
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowRepo = app(JobWorkflowRepository::class);
        $this->getInfo();
    }

    public function getInfo()
    {
        $this->workflowInfo = [];
        return "Workflow Id provided: $this->workflowId";
    }

    public function getExecutionStrategy()
    {
        if ($this->workflowId == 1 || $this->workflowId == 2) {
            return 'batch';
        }

        return 'sequential';
    }

    public function dispatch()
    {
        if (!$this->workflowId) {
            return false; // Return a non-zero status code to indicate failure
        }

        $jobWorkflowId = 0;
        try {
            $jobWorkflow = [
                'workflow_id' => $this->workflowId,
                'status' => 'CREATED',
                'total_no_of_records_to_execute' => 0,
                'total_no_of_records_executed' => 0,
                'response' => []
            ];
            $jobWorkflowId = $this->jobWorkflowRepo->createSingle($jobWorkflow);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return false;
        }

        if ($this->workflowId == 1) {
            $csvFile = storage_path('app/nfip_07_april.csv');
            $emailTemplate = storage_path('app/nfip_07_april.html');
            $plainEmailTemplate = storage_path('app/nfip_07_april.txt');
        }

        if ($this->workflowId == 2) {
            $csvFile = storage_path('app/nfip_08_april.csv');
            $emailTemplate = storage_path('app/nfip_08_april.html');
            $plainEmailTemplate = storage_path('app/nfip_08_april.txt');
        }

        if ($this->workflowId == 3) {
            $csvFile = storage_path('app/nfip_09_april.csv');
            $emailTemplate = storage_path('app/nfip_09_april.html');
            $plainEmailTemplate = storage_path('app/nfip_09_april.txt');
        }

        $actionPayload = [
            'workflowId' => $this->workflowId,
            'jobWorkflowId' => $jobWorkflowId,
            'actionType' => 'BulkEmail',
            'csvFile' => $csvFile,
            'emailTemplate' => $emailTemplate,
            'plainEmailTemplate' => $plainEmailTemplate,
            'subject' => 'Welcome to NFIP Direct System',
            'payload' => []
        ];

        $action = new BulkEmail();
        $action->setPayload($actionPayload);
        $action->execute();


        return true;
    }
}
