<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\UploadAsDocument;

use Avatar\Infrastructure\Models\Api\v1\DocumentUploadBatchModel;
use Avatar\Infrastructure\Models\Api\v1\TbClaimLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UploadAsDocumentService
{
    /**
     * Predefined module mappings used to resolve module identifiers
     * to their corresponding module names.
     *
     * @var array<int, array{moduleIdentifier:string, module:string}>
     */
    private const MODULES = [
        [
            'moduleIdentifier' => 'TbClaim',
            'module' => 'Claim',
        ],
        [
            'moduleIdentifier' => 'TbPotransaction',
            'module' => 'Policy',
        ],
        [
            'moduleIdentifier' => 'TbAgentTasksMaster',
            'module' => 'Policy',
        ],
        [
            'moduleIdentifier' => 'TbAgentTasksMasterMapping',
            'module' => 'Policy',
        ],
        [
            'moduleIdentifier' => 'TbQuotepolicy',
            'module' => '',
        ],
        [
            'moduleIdentifier' => 'TbPersonInfo',
            'module' => 'Producer',
        ],
        [
            'moduleIdentifier' => 'TbUser',
            'module' => '',
        ],
    ];

    public static function execute($module, $payload, $preparedData)
    {
        $recordIdentifier = $payload['recordIdentifier'] ?? null;
        $referenceNo = 0;
        $table = $module;
        $matchedModuleIdentifierData = self::moduleMatches(self::MODULES, $table);
        $attachmentModuleAndReferenceNo = [];

        if ($matchedModuleIdentifierData !== null && $recordIdentifier !== null) {
            $attachmentModuleAndReferenceNo = self::getAttachmentModuleAndReferenceNo($table, $matchedModuleIdentifierData, $recordIdentifier);
        }

        if ($attachmentModuleAndReferenceNo !== null) {
            $module = $attachmentModuleAndReferenceNo['module'];
            $referenceNo = $attachmentModuleAndReferenceNo['referenceNo'];
        }

        \Log::info('WORKFLOW - Document Upload - Attachment Module and Reference No', ['module' => $module, 'referenceNo' => $referenceNo]);

        $documentUploadBatchModel = new DocumentUploadBatchModel;
        $fileArray = [
            'module' => $module,
            'docName' => $preparedData['docName'],
            'docTypeValue' => $preparedData['docTypeValue'],
            'file' => [
                'fileExt' => 'pdf',
                'origintalFileName' => $preparedData['originalFileName'],
                'fileType' => $preparedData['fileType'],
                'fileSize' => '',
                'docUrl' => $preparedData['docUrl'],
                'docPath' => $preparedData['docPath'],
            ],
            'referenceNo' => $referenceNo,
        ];

        $isDocumentUploaded = $documentUploadBatchModel->uploadDocumentForAllModules([$fileArray], $throwException = true);

        if ($isDocumentUploaded && $module == 'Claim') {
            $claimLog = [
                'Inserted_UserId_FK' => Auth::check() ? user()->Admin_ID : 0,
                'InsertedBy_Flag' => $preparedData['insertedByFlag'],
                'ClaimtId_FK' => $recordIdentifier,
                'Claim_Activity_Log' => $preparedData['activityLogText'],
            ];

            $tbClaimLog = new TbClaimLog;
            $tbClaimLog->createAndSaveClaimLog($claimLog);
        }

        return [];
    }

    /**
     * Matches the payload module against predefined module identifiers
     * and returns the corresponding module data (name and identifier).
     *
     * @param  array  $moduleArray  Predefined module definitions.
     *
     * @example :
     *                           [
     *                             [
     *                               'moduleIdentifier' => 'TbClaim',
     *                               'module' => 'Claim',
     *                             ],
     *                             // ...
     *                           ]
     *
     * @param  string  $module  Module value received from the payload.
     * @return array|null Matched module data or null if no match is found.
     */
    public static function moduleMatches(array $moduleArray, string $module): ?array
    {
        foreach ($moduleArray as $value) {
            if (\Str::endsWith($module, $value['moduleIdentifier'])) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Resolves module and reference details for a given record.
     *
     * @param  string  $table  Fully qualified Eloquent model class.
     *
     * @example: 'Avatar/Infrastructure/Models/Api/v1/TbClaim' => TbClaim::class
     *
     * @param  array  $matchedModuleIdentifierData  this is the matched module data that is returned from the moduleMatches function which includes the module name and module identifier
     *
     * @example:
     *                     [
     *                       'moduleIdentifier' => 'TbClaim',
     *                       'module' => 'Claim',
     *                     ]
     *
     * @param  int  $recordIdentifier  Primary key value of the target record.
     *
     * @example: n_potransaction_PK for TbPotransaction, id for TbAgentTasksMaster, ClaimId_PK for TbClaim etc
     *
     * @return array{module:string,referenceNo:int,}|null
     */
    public static function getAttachmentModuleAndReferenceNo(string $table, array $matchedModuleIdentifierData, int $recordIdentifier): ?array
    {
        $moduleType = $matchedModuleIdentifierData['moduleIdentifier'] ?? null;
        $module = $matchedModuleIdentifierData['module'] ?? null;

        if (! $moduleType || ! $module || ! $recordIdentifier) {
            return null;
        }

        $record = $table::find($recordIdentifier);

        if (! $record) {
            return null;
        }

        $handlers = [
            'TbClaim' => 'getClaimModuleAndReferenceNo',
            'TbPotransaction' => 'getPolicyTransactionModuleAndReferenceNo',
            'TbAgentTasksMaster' => 'getAgentTasksModuleAndReferenceNo',
            'TbAgentTasksMasterMapping' => 'getAgentTasksMappingModuleAndReferenceNo',
            'TbQuotepolicy' => 'getQuotePolicyModuleAndReferenceNo',
            'TbPersonInfo' => 'getPersonInfoModuleAndReferenceNo',
            'TbUser' => 'getUserModuleAndReferenceNo',
        ];

        $handler = $handlers[$moduleType] ?? null;

        return $handler && is_callable(value: [self::class, $handler])
            ? self::$handler($record, $module)
            : null;
    }

    public static function getClaimModuleAndReferenceNo(object $recordInfo, string $module): array
    {
        return [
            'module' => $module,
            'referenceNo' => $recordInfo->Claim_No ?? '',
        ];
    }

    public static function getPolicyTransactionModuleAndReferenceNo(object $recordInfo, string $module): array
    {
        $policyNo = DB::table('tb_policies as policy')
            ->where('n_PolicyNoId_PK', '=', $recordInfo->n_PolicyMaster_FK)
            ->value('policy.Policy_No') ?? '';

        return [
            'module' => $module,
            'referenceNo' => $policyNo,
        ];
    }

    public static function getAgentTasksModuleAndReferenceNo(object $recordInfo, string $module): array
    {
        $policyNo = DB::table('tb_policies as policy')
            ->where('n_PolicyNoId_PK', '=', $recordInfo->policymaster_FK)
            ->value('policy.PolicyNo') ?? '';

        return [
            'module' => $module,
            'referenceNo' => $policyNo,
        ];
    }

    public static function getAgentTasksMappingModuleAndReferenceNo(object $recordInfo, string $module): array
    {
        $policyId = DB::table('tb_agent_tasks_master as atm')
            ->leftJoin('tb_agent_tasks_master_mapping as atmm', 'atmm.agent_tasks_masters_id', '=', 'atm.id')
            ->where('atmm.id', '=', $recordInfo->id)
            ->value('atm.policymaster_FK');

        $policyNo = DB::table('tb_policies as policy')
            ->where('n_PolicyNoId_PK', '=', $policyId)
            ->value('policy.Policy_No') ?? '';

        return [
            'module' => $module,
            'referenceNo' => $policyNo,
        ];
    }

    public static function getQuotePolicyModuleAndReferenceNo(object $recordInfo, string $module): array
    {
        // TODO:: get $referenceNo
        // TODO: implement in infra/DocumentUploadBatchModel
        // TODO: Need discussion
        $module = '????';

        return [
            'module' => $module,
            'referenceNo' => '',
        ];
    }

    public static function getPersonInfoModuleAndReferenceNo(object $recordInfo, string $module): array
    {
        $statementId = DB::table('tb_accountmasters as am')
            ->where('am.n_PersonInfoId_FK', '=', $recordInfo->n_PersonInfoId_PK)
            ->leftJoin('tb_paagentstatementmasters as pasm', 'pasm.n_PAAgentMasterFK', '=', 'am.n_AgencyAddlInfoId_PK')
            ->value('pasm.n_PAAgentStatementMaster_PK') ?? '';

        return [
            'module' => $module,
            'referenceNo' => $statementId,
        ];
    }

    public static function getUserModuleAndReferenceNo(object $recordInfo, string $module): array
    {
        // TODO:: get $referenceNo
        // TODO: implement in infra/DocumentUploadBatchModel
        $module = '?????';

        return [
            'module' => $module,
            'referenceNo' => '',
        ];
    }
}
