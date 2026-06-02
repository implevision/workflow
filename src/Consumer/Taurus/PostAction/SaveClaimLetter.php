<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction;

class SaveClaimLetter
{
    public static function execute($module, $payload, $preparedData)
    {
        \Log::info('WORKFLOW: Executing SaveClaimLetter post action', [
            'module' => $module,
            'payload' => $payload,
            'preparedData' => $preparedData,
        ]);
        $data = [
            'claimid' => $payload['recordIdentifier'] ?? null,
            'docFor' => $payload['actionPayload']['saveClaimLetter']['createDocumentCopyFor'],
            'isPreview' => 'N',
            'otherInfo' => [
                'recipientName' => $payload['actionPayload']['saveClaimLetter']['recipientName'] ?? '',
                'address' => $payload['actionPayload']['saveClaimLetter']['address'] ?? '',
                'city' => $payload['actionPayload']['saveClaimLetter']['city'] ?? '',
                'state' => $payload['actionPayload']['saveClaimLetter']['state'] ?? '',
                'postalCode' => $payload['actionPayload']['saveClaimLetter']['postalCode'] ?? '',
            ],
            'template' => $preparedData['templateId'],
            'text' => $preparedData['htmlContent'] ?? '',
        ];
        \App\Services\Claim\Claim::getClaimLetterGenerate($data);
    }
}
