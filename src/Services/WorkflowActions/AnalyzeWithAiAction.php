<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Taurus\Workflow\Events\PostActionEvent;

/**
 * ANALYZE_WITH_AI action: analyzes the record's uploaded document and saves the summary as a document.
 */
class AnalyzeWithAiAction extends AbstractWorkflowAction
{
    private const DOCUMENT_PLACEHOLDER = 'AttachUploadedDocument';

    public function handle() {}

    public function getListOfRequiredData()
    {
        return [self::DOCUMENT_PLACEHOLDER];
    }

    public function getListOfMandateData()
    {
        return [self::DOCUMENT_PLACEHOLDER];
    }

    public function execute()
    {
        $data = $this->getData();

        if ($this->getFeedFile() || empty($data)) {
            return false;
        }

        $results = array_map(fn ($row) => $this->analyze($row[self::DOCUMENT_PLACEHOLDER][0]), $data);

        Event::dispatch(new PostActionEvent(getModuleForCurrentWorkflow(), [
            'workflowId' => $this->getWorkflowId(),
            'jobWorkflowId' => $this->getJobWorkflowId(),
            'recordIdentifier' => $this->getRecordIdentifier(),
            'postAction' => 'uploadAiAnalysis',
            'payload' => $results,
        ], (string) $this->getJobWorkflowId()));

        return true;
    }

    private function analyze(array $document): array
    {
        $payload = $this->getPayload();
        $config = config('workflow.ai_service');

        $fields = array_filter([
            'system_prompt' => $payload['systemPrompt'] ?? null,
            'analysis_instructions' => $payload['analysisInstructions'] ?? null,
            's3_bucket' => $config['s3_bucket'],
            'model' => ($payload['model'] ?? null) ?: $config['default_model'],
            'output_format' => ($payload['outputFormat'] ?? null) ?: 'json',
            's3_key_prefix' => $config['s3_key_prefix'],
            'max_tokens' => $config['max_tokens'],
            'temperature' => $config['temperature'],
        ], fn ($value) => $value !== null && $value !== '');

        $response = Http::attach('file', Http::get($document['path'])->throw()->body(), $document['name'])
            ->post(rtrim($config['url'], '/').'/api/v1/analyze', $fields)
            ->throw()
            ->json('data');

        return [...$response, 'documentName' => $document['name']];
    }
}
