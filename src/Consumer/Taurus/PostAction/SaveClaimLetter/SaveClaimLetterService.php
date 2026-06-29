<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\SaveClaimLetter;

use App\Services\Claim\Claim;

class SaveClaimLetterService
{
    public static function execute($module, $payload, $preparedData)
    {
        try {
            $data = [
                'claimid' => $payload['recordIdentifier'] ?? null,
                'docFor' => $payload['actionPayload']['saveClaimLetter']['createDocumentCopyFor'] ?? null,
                'isPreview' => 'N',
                'otherInfo' => [
                    'recipientName' => $payload['actionPayload']['saveClaimLetter']['recipientName'] ?? '',
                    'address' => $payload['actionPayload']['saveClaimLetter']['address'] ?? '',
                    'city' => $payload['actionPayload']['saveClaimLetter']['city'] ?? '',
                    'state' => $payload['actionPayload']['saveClaimLetter']['state'] ?? '',
                    'postalCode' => $payload['actionPayload']['saveClaimLetter']['postalCode'] ?? '',
                ],
                'template' => $preparedData['claimLetterTemplateId'] ?? null,
                'text' => $preparedData['htmlContent'] ?? '',
            ];
            \Log::info('WORKFLOW - Saving claim letter for claim ID: '.$data['claimid'], [
                'template' => $data['template'],
            ]);

            Claim::getClaimLetterGenerate($data);
        } catch (\Exception $e) {
            \Log::error('WORKFLOW - Error saving claim letter: '.$e->getMessage(), [
                'module' => $module,
                'recordIdentifier' => $payload['recordIdentifier'] ?? null,
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }
}
