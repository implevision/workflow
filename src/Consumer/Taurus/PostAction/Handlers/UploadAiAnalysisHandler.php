<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\Handlers;

use Taurus\Workflow\Consumer\Taurus\PostAction\UploadAsDocument\UploadAsDocumentService;
use Taurus\Workflow\Services\AWS\S3;

/**
 * Copies the AI analysis output into the documents bucket and attaches it to the record.
 */
class UploadAiAnalysisHandler implements PostActionHandlerInterface
{
    private const DOC_TYPE_VALUE = '';

    public function prepare(array $payload, array $placeholders, string $messageId): array
    {
        $docName = $placeholders['documentName'].'_summary_'.date('YmdHis');
        $filename = $docName.'.'.pathinfo($placeholders['s3_key'], PATHINFO_EXTENSION);
        $docPath = sprintf('%s/%s/%s/%s/aiAnalysis/%s', getTenant(), date('Y'), date('m'), date('d'), $filename);
        $bucketName = config('workflow.bucket_to_save_email_letters', config('filesystems.disks.s3.bucket'));

        S3::uploadFile($bucketName, $docPath, S3::getInfo($placeholders['s3_bucket'], $placeholders['s3_key']));

        return [
            'docTypeValue' => self::DOC_TYPE_VALUE,
            'docName' => $docName,
            'originalFileName' => $filename,
            'fileType' => 'text/markdown',
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
