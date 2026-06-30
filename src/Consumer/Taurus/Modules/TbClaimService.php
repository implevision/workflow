<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

use App\Services\Claim\OnDemandWorkflowService;

class TbClaimService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'claim';
    }

    public function getExtendedTemplateInfo(array $templatePayload = []): array
    {
        try {
            $payload = [
                'recordIdentifier' => getRecordIdentifierForRunningWorkflow() ?? null,
                'workflowTemplateId' => $templatePayload['id'] ?? null,
            ];

            return OnDemandWorkflowService::getClaimIdFromTemplatePayload($payload);
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error getting extended template info for TbClaim.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [];
        }
    }
}
