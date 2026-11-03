<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Taurus\Workflow\Consumer\Taurus\PostAction\UploadAsDocument\UploadAsDocumentService;
use Taurus\Workflow\Services\AWS\S3;

/**
 * Copies the AI analysis output into the documents bucket and attaches it to the record.
 */
class UploadAiAnalysisHandler implements PostActionHandlerInterface
{
    private const DOC_TYPE_CODE = 'AI_ANALYSIS';

    private const FILE_TYPES = [
        'markdown' => 'text/markdown',
        'text' => 'text/plain',
        'html' => 'text/html',
        'json' => 'application/json',
    ];

    public function prepare(array $payload, array $placeholders, string $messageId): array
    {
        $docName = $placeholders['documentName'].'_summary_'.date('YmdHis');
        $filename = $docName.'_'.Str::uuid().'.'.pathinfo($placeholders['s3_key'], PATHINFO_EXTENSION);
        $docPath = sprintf('%s/%s/%s/%s/aiAnalysis/%s', getTenant(), date('Y'), date('m'), date('d'), $filename);
        $bucketName = config('workflow.bucket_to_save_email_letters', config('filesystems.disks.s3.bucket'));

        S3::uploadFile($bucketName, $docPath, S3::getInfo($placeholders['s3_bucket'], $placeholders['s3_key']));

        return [
            'docTypeValue' => DB::table('tb_doctypes')->where('s_DocTypeCode', self::DOC_TYPE_CODE)->value('n_DocType_PK'),
            'docName' => $docName,
            'originalFileName' => $filename,
            'fileType' => self::FILE_TYPES[$placeholders['output_format'] ?? ''] ?? 'text/plain',
            'docPath' => $docPath,
            'docUrl' => '',
            'insertedByFlag' => 'System',
            'activityLogText' => "AI analysis summary for '".$placeholders['documentName']."' generated and uploaded.",
        ];
    }

    public function execute(string $module, array $payload, array $preparedData): mixed
    {
        return UploadAsDocumentService::execute($module, $payload, $preparedData);
    }
}
