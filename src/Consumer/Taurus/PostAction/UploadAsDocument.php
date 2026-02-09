<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction;

use Avatar\Infrastructure\Models\Api\v1\DocumentUploadBatchModel;
use Avatar\Infrastructure\Models\Api\v1\TbClaimLog;
use Avatar\Infrastructure\Models\Api\v1\TbPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UploadAsDocument
{
    public static function execute($module, $payload, $preparedData)
    {
        $recordIdentifier = $payload['recordIdentifier'] ?? null;
        $referenceNo = 0;

        if (str_ends_with($module, 'TbClaim')) {
            $recordInfo = $module::find($recordIdentifier);
            $referenceNo = $recordInfo->Claim_No ?? '';
            $module = 'Claim';
        }

        if (str_ends_with($module, 'TbPotransaction')) {
            $recordInfo = $module::find($recordIdentifier);
            $policyNo = DB::table('tb_potransactions')
                ->where('n_potransaction_PK', '=', $recordIdentifier)
                ->leftJoin('tb_policies', 'n_Policy_Master_FK', '=', 'n_PolicyNoId_PK')
                ->value('tb_policies.PolicyNo');
            $referenceNo = $policyNo ?? '';
            $module = 'Policy';
        }

        $docTypeValue = $preparedData['docTypeValue'];
        $docName = $preparedData['docName'];
        $docUrl = $preparedData['docUrl'];
        $originalFileName = $preparedData['originalFileName'];
        $fileType = $preparedData['fileType'];
        $docPath = $preparedData['docPath'];
        $docUrl = $preparedData['docUrl'];

        $documentUploadBatchModel = new DocumentUploadBatchModel;
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
            'referenceNo' => $referenceNo,
        ];

        try {
            $isDocumentUploaded = $documentUploadBatchModel->uploadDocumentForAllModules([$fileArray], $throwException = true);
        } catch (\Exception $e) {
            throw $e;
        }

        if ($isDocumentUploaded && $module == 'Claim') {
            $insertedByFlag = $preparedData['insertedByFlag'];
            $activityLogText = $preparedData['activityLogText'];
            $claimLog = [
                'Inserted_UserId_FK' => Auth::check() ? user()->Admin_ID : 0,
                'InsertedBy_Flag' => $insertedByFlag,
                'ClaimtId_FK' => $recordIdentifier,
                'Claim_Activity_Log' => $activityLogText,
            ];

            try {
                $tbClaimLog = new TbClaimLog;
                $tbClaimLog->createAndSaveClaimLog($claimLog);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return [];
    }
}
