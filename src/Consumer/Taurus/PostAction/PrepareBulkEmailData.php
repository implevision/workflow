<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction;

use Dompdf\Dompdf;
use Taurus\Workflow\Services\S3;

class PrepareBulkEmailData
/**
 * Class PrepareBulkEmailData
 *
 * This class is responsible for preparing bulk email data for processing.
 * It handles the necessary transformations and validations required
 * before the bulk email is sent out.
 */
{
    /**
     * Prepares the bulk email data based on the provided payload and placeholders.
     *
     * @param  mixed  $payload  The data to be processed for the bulk email.
     * @param  array  $placeholders  An associative array of placeholders to be replaced in the email content.
     * @return mixed The processed bulk email data.
     */
    public static function prepare($payload, $placeholders, $messageId)
    {
        $pageSize = 'A4';
        $pageOrientation = 'portrait';

        // CREATE S3 PATH
        try {
            $html = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($placeholders) {
                $key = trim($matches[1]);

                return $placeholders[$key] ?? $matches[0]; // fallback to original if key not found
            }, $payload['emailTemplate']);

            $pdfBuffer = self::htmlToPdf($html, $pageSize, $pageOrientation);
        } catch (\Exception $e) {
            throw $e;
        }

        $documentName = $payload['actionPayload']['documentName'] ?? '';
        $documentId = $payload['actionPayload']['documentId'] ?? '';

        $filename = preg_replace('/[^A-Za-z0-9 ]/', '', $payload['subject'])." - {$documentName}.pdf";
        $docPath = sprintf('%s/%s/%s/%s/emailLetters/%s', getTenant(), date('Y'), date('m'), date('d'), $filename);
        $bucketName = config('workflow.bucket_to_save_email_letters', config('filesystems.disks.s3.bucket'));

        try {
            $docUrl = S3::uploadFile($bucketName, $docPath, $pdfBuffer);
        } catch (\Exception $e) {
            throw $e;
        }

        return [
            'docTypeValue' => $documentId,
            'docName' => $documentName,
            'originalFileName' => $filename,
            'fileType' => 'application/pdf',
            'docPath' => $docPath,
            'docUrl' => $docUrl,
            'insertedByFlag' => 'System',
            'activityLogText' => "Email letter for '".$documentName."' generated and uploaded. Message ID - ".$messageId,
        ];
    }

    /**
     * Converts HTML content to a PDF document.
     *
     * @param  string  $html  The HTML content to be converted.
     * @param  string  $pageSize  The size of the pages in the PDF (e.g., 'A4', 'Letter').
     * @param  string  $pageOrientation  The orientation of the pages in the PDF (e.g., 'portrait', 'landscape').
     * @return mixed The generated PDF document or an error message on failure.
     */
    public static function htmlToPdf($html, $pageSize, $pageOrientation)
    {
        try {
            $domPdf = new Dompdf;
            $domPdf->loadHtml($html);
            $domPdf->setPaper($pageSize, $pageOrientation);
            $domPdf->render();

            return $domPdf->output();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
