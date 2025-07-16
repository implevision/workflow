<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction;

use Avatar\Infrastructure\Models\Api\v1\DocumentUploadBatchModel;
use Avatar\Infrastructure\Models\Api\v1\TbClaimLog;
use Illuminate\Support\Facades\Auth;

class UploadAsDocument
{
    public static function execute($module, $payload, $preparedData)
    {
        $recordIdentifier = $payload['recordIdentifier'] ?? null;

        $docTypeValue = $preparedData['docTypeValue'];
        $docName = $preparedData['docName'];
        $docUrl = $preparedData['docUrl'];
        $originalFileName = $preparedData['originalFileName'];
        $fileType = $preparedData['fileType'];
        $docPath = $preparedData['docPath'];
        $docUrl = $preparedData['docUrl'];

        $documentUploadBatchModel = new DocumentUploadBatchModel();
        $fileArray = [
            'module' => $module,
            'docName' => $docName,
            'docTypeValue' => $docTypeValue,
            'file' => [
                'fileExt' => 'pdf',
                'origintalFileName' => $originalFileName,
                'fileType' => $fileType,
                'fileSize' => '',
                'docUrl' => $docUrl,
                'docPath' => $docPath,
            ],
            'referenceNo' => $recordIdentifier,
        ];

        try {
            $isDocumentUploaded = $documentUploadBatchModel->uploadDocumentForAllModules([$fileArray], $throwException = true);
        } catch (\Exception $e) {
            throw $e;
        }

        if ($isDocumentUploaded && str_ends_with($module, 'TbClaim')) {
            $insertedByFlag = $preparedData['insertedByFlag'];
            $activityLogText = $preparedData['activityLogText'];
            $claimLog = [
                'Inserted_UserId_FK' => Auth::check() ? user()->Admin_ID : 0,
                'InsertedBy_Flag' => $insertedByFlag,
                'ClaimtId_FK' => $recordIdentifier,
                'Claim_Activity_Log' => $activityLogText
            ];

            try {
                $tbClaimLog = new TbClaimLog();
                $tbClaimLog->createAndSaveClaimLog($claimLog);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return [];
    }
}
