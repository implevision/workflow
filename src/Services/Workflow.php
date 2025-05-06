<?php

namespace Taurus\Workflow\Services;

use Taurus\Workflow\Services\WorkflowActions\BulkEmail;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Services\WorkflowService;

class Workflow
{
    private $workflowId;

    private $workflowInfo = null;

    protected $jobWorkflowRepo;

    protected $workflowService;

    protected $isWorkflowLive;

    public function __construct(int $workflowId)
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowRepo = app(JobWorkflowRepository::class);
        //$this->workflowService = app(WorkflowService::class);
        $this->isWorkflowLive = config('workflow.is_workflow_live');
        //$this->getInfo();
    }

    public function getInfo()
    {
        $this->workflowInfo = $this->workflowService->getWorkflowDetailsById($this->workflowId);
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


        if ($this->isWorkflowLive) {
            \Log::info('Executing workflow with ID: ' . $this->workflowId);
            \Log::info('Workflow Name: ' . $this->workflowInfo['detail']['name']);
            $allConditions = $this->workflowInfo['workFlowConditions'];

            foreach ($allConditions as $condition) {
                if ($condition['customCSVFeed']) {
                    //get csv file from S3
                }

                foreach ($condition['instanceActions'] as $action) {
                    if ($action['actionType'] == 'EMAIL') {
                    }
                }
            }

            //get email template

            //get csv file
        } else {
            if ($this->workflowId == 1) {
                $csvFile = storage_path('app/nfip_07_april.csv');
                $emailTemplate = storage_path('app/nfip_07_april.html');
                $plainEmailTemplate = storage_path('app/nfip_07_april.txt');
                $subject = 'Welcome to NFIP Direct System';
            }

            if ($this->workflowId == 2) {
                $csvFile = storage_path('app/nfip_08_april.csv');
                $emailTemplate = storage_path('app/nfip_08_april.html');
                $plainEmailTemplate = storage_path('app/nfip_08_april.txt');
                $subject = 'Login to the New NFIP Direct System';
            }

            if ($this->workflowId == 3) {
                $csvFile = storage_path('app/nfip_09_april.csv');
                $emailTemplate = storage_path('app/nfip_09_april.html');
                $plainEmailTemplate = storage_path('app/nfip_09_april.txt');
                $subject = 'The NFIP Direct Flood Experience';
            }

            if ($this->workflowId == 4) {
                $csvFile = storage_path('app/nfip_14_april.csv');
                $emailTemplate = storage_path('app/nfip_14_april.html');
                $plainEmailTemplate = storage_path('app/nfip_14_april.txt');
                $subject = 'NFIP Direct System: Simplifying the Flood Experience';
            }

            if ($this->workflowId == 5) {
                $csvFile = storage_path('app/nfip_21_april.csv');
                $emailTemplate = storage_path('app/nfip_21_april.html');
                $plainEmailTemplate = storage_path('app/nfip_21_april.txt');
                $subject = 'Endorsement Transactions and Policy Search';
            }

            if ($this->workflowId == 6) {
                $csvFile = storage_path('app/nfip_28_april.csv');
                $emailTemplate = storage_path('app/nfip_28_april.html');
                $plainEmailTemplate = storage_path('app/nfip_28_april.txt');
                $subject = 'The Flood Revolution!';
            }

            if ($this->workflowId == 7) {
                $csvFile = storage_path('app/nfip_05_may.csv');
                $emailTemplate = storage_path('app/nfip_05_may.html');
                $plainEmailTemplate = storage_path('app/nfip_05_may.txt');
                $subject = 'Action Required: FIRA Certification Needed for NFIP Direct System';
            }

            if ($this->workflowId == 8) {
                $csvFile = storage_path('app/nfip_12_may.csv');
                $emailTemplate = storage_path('app/nfip_12_may.html');
                $plainEmailTemplate = storage_path('app/nfip_12_may.txt');
                $subject = 'Flood Marketing & Training Resources';
            }

            if ($this->workflowId == 9) {
                $csvFile = storage_path('app/nfip_01_may.csv');
                $emailTemplate = storage_path('app/nfip_01_may.html');
                $plainEmailTemplate = storage_path('app/nfip_01_may.txt');
                $subject = 'Sign Up for New NFIP Direct System Trainings';
            }

            $actionPayload = [
                'workflowId' => $this->workflowId,
                'jobWorkflowId' => $jobWorkflowId,
                'actionType' => 'BulkEmail',
                'csvFile' => $csvFile,
                'emailTemplate' => $emailTemplate,
                'plainEmailTemplate' => $plainEmailTemplate,
                'subject' => $subject,
                'payload' => []
            ];

            $action = new BulkEmail();
            $action->setPayload($actionPayload);
            $action->execute();
        }


        return true;
    }
}
