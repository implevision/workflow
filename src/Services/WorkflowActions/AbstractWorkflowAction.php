<?php

namespace Taurus\Workflow\Services\WorkflowActions;

class AbstractWorkflowAction
{
    protected $action;

    protected $payload;

    protected $workflowId;

    protected $jobWorkflowId;

    protected $recordIdentifier;

    protected $module = '';

    protected $data;

    protected $feedFile;

    protected array $extendedTemplateInfo = [];

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

    public function updatePayload($key, $params)
    {
        return $this->payload = [...$this->payload, ...[$key => $params]];
    }

    public function getWorkflowId()
    {
        return $this->workflowId;
    }

    public function getJobWorkflowId()
    {
        return $this->jobWorkflowId;
    }

    public function getRecordIdentifier()
    {
        return $this->recordIdentifier;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getFeedFile()
    {
        return $this->feedFile;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function setWorkflowData($workflowId, $jobWorkflowId, $recordIdentifier, $module = '')
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowId = $jobWorkflowId;
        $this->recordIdentifier = $recordIdentifier;
        $this->module = $module;
    }

    public function setDataForAction($feedFile, $data)
    {
        $this->feedFile = $feedFile;
        $this->data = $data;
    }

    public function setExtendedTemplateInfo(array $templateInfo): void
    {
        $this->extendedTemplateInfo = $templateInfo;
    }

    public function getExtendedTemplateInfo(): array
    {
        return $this->extendedTemplateInfo;
    }

    public function getListOfRequiredData()
    {
        // This method should be overridden in subclasses to return the required data for the action
        throw new \Exception('getListOfRequiredData method must be implemented in the subclass.');
    }

    public function getListOfMandateData()
    {
        // This method should be overridden in subclasses to return the required data for the action
        throw new \Exception('getListOfMandateData method must be implemented in the subclass.');
    }

    public function handle()
    {
        // This method should be overridden in subclasses to return the required data for the action
        throw new \Exception('handle method must be implemented in the subclass.');
    }

    public function execute()
    {
        // This method should be overridden in subclasses to return the required data for the action
        throw new \Exception('execute method must be implemented in the subclass.');
    }
}
