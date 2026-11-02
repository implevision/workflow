<?php

namespace Taurus\Workflow\Tests\Support;

use Taurus\Workflow\Data\WorkflowData;
use Taurus\Workflow\Services\WorkflowService;

class FakeWorkflowService extends WorkflowService
{
    public ?WorkflowData $workflow = null;

    public FakeModuleSchema $moduleSchema;

    public array|\Throwable|null $recordIdentifierQuery = null;

    public array $effectiveActionQuery = [];

    public array $extendedTemplateInfo = [];

    public array $errors = [];

    public array $mappingServiceCalls = [];

    public array $effectiveActionQueryCalls = [];

    public array $scheduledWorkflows = [];

    public function __construct()
    {
        $this->moduleSchema = new FakeModuleSchema;
    }

    public function getWorkflowDetailsById(int $workflowId, bool $withDeleted = false): WorkflowData
    {
        return $this->workflow ?? throw new \Exception('No data found for the provided workflow ID: '.$workflowId);
    }

    public function getGraphQLQueryMappingService($module, $appendPlaceHolders = [])
    {
        $this->mappingServiceCalls[] = ['module' => $module, 'appendPlaceHolders' => $appendPlaceHolders];
        $this->moduleSchema->setAppendedPlaceHolders($appendPlaceHolders);

        return $this->moduleSchema;
    }

    public function getQueryForRecordIdentifier($module, $recordIdentifier)
    {
        if ($this->recordIdentifierQuery instanceof \Throwable) {
            throw $this->recordIdentifierQuery;
        }

        return $this->recordIdentifierQuery ?? ['column' => 'POLICY_ID', 'operator' => 'EQ', 'value' => $recordIdentifier];
    }

    public function getQueryForEffectiveAction($module, $executionFrequency, $executionFrequencyType, $executionEventIncident, $executionEvent)
    {
        $this->effectiveActionQueryCalls[] = func_get_args();

        return $this->effectiveActionQuery;
    }

    public function getExtendedTemplateInfoForModule(string $module, array $templatePayload = []): array
    {
        return $this->extendedTemplateInfo;
    }

    public function scheduleWorkflows($workflows)
    {
        $this->scheduledWorkflows[] = $workflows;
    }

    public function addWorkflowLog($workflowId, $jobWorkflowId, $errorType, $exception = null): void
    {
        $this->errors[] = ['errorType' => $errorType, 'exception' => $exception];
    }
}
