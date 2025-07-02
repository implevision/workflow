<?php

namespace Taurus\Workflow\Services\WorkflowActions;

class AbstractWorkflowAction
{

    protected $action;

    protected $payload;

    protected $workflowId;

    protected $jobWorkflowId;

    protected $data;

    protected $feedFile;

    public function __construct($action, $payload)
    {
        $this->action = $action;
        $this->payload = $payload;
        // Initialization code can go here if needed
    }

    public function getCurrentAction()
    {
        return $this->action;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getWorkflowId()
    {
        return $this->workflowId;
    }

    public function getJobWorkflowId()
    {
        return $this->jobWorkflowId;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getFeedFile()
    {
        return $this->feedFile;
    }

    public function setWorkflowData($workflowId, $jobWorkflowId)
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowId = $jobWorkflowId;
    }

    public function setDataForAction($feedFile, $data)
    {
        $this->feedFile = $feedFile;
        $this->data = $data;
    }

    public function getRequiredData()
    {
        // This method should be overridden in subclasses to return the required data for the action
        throw new \Exception("getRequiredData method must be implemented in the subclass.");
    }

    public function handle()
    {
        // This method should be overridden in subclasses to return the required data for the action
        throw new \Exception("handle method must be implemented in the subclass.");
    }

    public function execute()
    {
        // This method should be overridden in subclasses to return the required data for the action
        throw new \Exception("execute method must be implemented in the subclass.");
    }
}
