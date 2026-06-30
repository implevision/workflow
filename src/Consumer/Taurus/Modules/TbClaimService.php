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
        $payload = [
            'recordIdentifier' => getRecordIdentifierForRunningWorkflow() ?? null,
            'workflowTemplateId' => $templatePayload['id'] ?? null,
        ];

        return OnDemandWorkflowService::getClaimIdFromTemplatePayload($payload);
    }
}
