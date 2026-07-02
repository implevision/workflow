<?php

namespace Taurus\Workflow\Services\WorkflowActions\Helpers\WorkflowOutput;

use Illuminate\Support\Facades\Event;
use Taurus\Workflow\Events\PostActionEvent;
use Taurus\Workflow\Services\WorkflowService;

class PrepareWorkflowOutputData
{
    protected $workflowId;

    protected $jobWorkflowId;

    protected $recordIdentifier;

    protected $templateId;

    protected $payload;

    protected $templateInformation;

    public function prepare($workflowId, $jobWorkflowId, $recordIdentifier, $templateId, $payload, $templateInformation)
    {
        $this->workflowId = $workflowId;
        $this->jobWorkflowId = $jobWorkflowId;
        $this->recordIdentifier = $recordIdentifier;
        $this->templateId = $templateId;
        $this->payload = $payload;
        $this->templateInformation = $templateInformation;

        return $this;
    }

    public function execute(): array
    {
        $data = $this->payload['data'] ?? [];
        $postAction = $this->payload['postAction'] ?? '';
        $outputActionType = $this->payload['actionPayload']['outputActionType'] ?? '';

        $this->generateOutput($outputActionType, $data);

        if ($postAction) {
            $actionPayload = [
                'workflowId' => $this->workflowId,
                'jobWorkflowId' => $this->jobWorkflowId,
                'recordIdentifier' => $this->recordIdentifier,
                'actionType' => 'workflow_output',
                'data' => $data,
                'payload' => $data,
                'emailTemplate' => $this->templateInformation['html'] ?? '',
                'subject' => $this->templateInformation['subject'] ?? '',
                'outputActionType' => $outputActionType,
                'postAction' => $postAction,
                'actionPayload' => $this->payload['actionPayload'] ?? [],
                'letterEditorMode' => $this->templateInformation['letterEditorMode'] ?? null,
                'pdfS3Key' => $this->templateInformation['pdfS3Key'] ?? null,
                'pdfPlaceholders' => $this->templateInformation['pdfPlaceholders'] ?? null,
                'module' => getModuleForCurrentWorkflow(),
            ];

            \Log::info('WORKFLOW - Executing post action for workflow output');
            $listenerResults = Event::dispatch(
                new PostActionEvent($actionPayload['module'], $actionPayload, (string) $this->jobWorkflowId)
            );

            // Event::dispatch() returns an array of each listener's return value; flatten them into one result list.
            return array_merge(...array_map(fn ($r) => \is_array($r) ? $r : [], (array) $listenerResults));
        }

        return [];
    }

    private function generateOutput(string $outputActionType, array $data)
    {
        switch ($outputActionType) {
            case 'PRINT_AS_PDF':
                \Log::info('WORKFLOW - Generating PDF output');
                $printAsPdf = new PrintAsPdf;
                $printAsPdf->generate($this->jobWorkflowId, $data, $this->templateInformation);
                break;

            case 'EXECUTE_POST_ACTION':
                \Log::info('WORKFLOW - Proceeding to execute post action for workflow output');
                break;

            default:
                throw new \Exception("Unsupported output action type: {$outputActionType}");
        }
    }
}
