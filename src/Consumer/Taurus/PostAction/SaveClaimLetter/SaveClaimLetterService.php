<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\SaveClaimLetter;

use App\Services\Claim\Claim;

class SaveClaimLetterService
{
    public static function execute($module, $payload, $preparedData)
    {
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
        return Claim::getClaimLetterGenerate($data);
    }
}
