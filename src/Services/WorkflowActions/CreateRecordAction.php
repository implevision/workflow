<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Taurus\Workflow\Services\WorkflowService;

/**
 * CREATE_RECORD action.
 *
 * Payload shape: ['module' => 'agentTask', 'fieldMapping' => [...]]. The field mapping may
 * contain {{Placeholder}} values which are resolved per record before the consumer's
 * handler for the module creates the record.
 */
class CreateRecordAction extends AbstractWorkflowAction
{
    public function handle()
    {
        $payload = $this->getPayload();

        if (empty($payload['module']) || ! is_string($payload['module'])) {
            throw new \Exception('Module is required to create a record.');
        }

        if (! isset($payload['fieldMapping']) || ! is_array($payload['fieldMapping'])) {
            throw new \Exception('Field mapping is required to create a record.');
        }

        $createRecordService = app(WorkflowService::class)->getCreateRecordService();

        if (! method_exists($createRecordService, 'supports') || ! $createRecordService->supports($payload['module'])) {
            throw new \Exception('Create record is not supported for module: '.$payload['module']);
        }
    }

    public function getListOfRequiredData()
    {
        $payload = $this->getPayload();

        if (! empty($payload['extractedPlaceholders'])) {
            return $payload['extractedPlaceholders'];
        }

        $placeholders = [];
        $search = $payload['fieldMapping'] ?? [];
        array_walk_recursive($search, function ($value) use (&$placeholders) {
            if (is_string($value)) {
                preg_match_all('/{{\s*(.*?)\s*}}/', $value, $matches);
                $placeholders = array_merge($placeholders, $matches[1]);
            }
        });

        return array_values(array_unique($placeholders));
    }

    public function getListOfMandateData()
    {
        $payload = $this->getPayload();

        return ! empty($payload['mandatoryPlaceholders']) ? $payload['mandatoryPlaceholders'] : [];
    }

    public function execute()
    {
        $payload = $this->getPayload();
        $data = $this->getData();

        if ($this->getFeedFile() || empty($data)) {
            return false;
        }

        $createRecordService = app(WorkflowService::class)->getCreateRecordService();
        $context = [
            'workflowId' => $this->getWorkflowId(),
            'jobWorkflowId' => $this->getJobWorkflowId(),
            'recordIdentifier' => $this->getRecordIdentifier(),
            'workflowModule' => $this->getModule(),
        ];

        foreach ($data as $placeHolderData) {
            $resolvedPayload = $this->replacePlaceholders($payload['fieldMapping'], $placeHolderData);

            try {
                // The source record lets the handler link the created record back to it.
                $createRecordService->execute($payload['module'], $resolvedPayload, $context + ['sourceRecord' => $placeHolderData]);
            } catch (\Exception $e) {
                throw new \Exception('Create record execution failed: '.$e->getMessage());
            }
        }

        return true;
    }

    private function replacePlaceholders($input, array $placeholders)
    {
        if (is_array($input)) {
            return array_map(fn ($item) => $this->replacePlaceholders($item, $placeholders), $input);
        }

        if (! is_string($input)) {
            return $input;
        }

        return preg_replace_callback('/{{\s*(.*?)\s*}}/', function ($matches) use ($placeholders) {
            $value = $placeholders[$matches[1]] ?? '';

            return is_scalar($value) ? $value : json_encode($value);
        }, $input);
    }
}
