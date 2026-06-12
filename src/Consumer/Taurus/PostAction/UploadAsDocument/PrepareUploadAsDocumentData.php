<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\UploadAsDocument;

use Dompdf\Dompdf;
use Taurus\Workflow\Consumer\Taurus\Helper;
use Taurus\Workflow\Services\AWS\S3;
use Taurus\Workflow\Services\WorkflowActions\Helpers\WorkflowOutput\PdfStamper;
use Taurus\Workflow\Services\WorkflowEmailService;

/**
 * Class PrepareUploadAsDocumentData
 *
 * This class is responsible for preparing data for the "upload as document" post action.
 * It handles the necessary transformations and validations required
 * before the document is uploaded.
 */
class PrepareUploadAsDocumentData
{
    /**
     * Prepares the data for the "upload as document" post action based on the provided payload and placeholders.
     *
     * @param  mixed  $payload  The data to be processed for the document.
     * @param  array  $placeholders  An associative array of placeholders to be replaced in the document content.
     * @return mixed The processed document data.
     */
    public static function prepare($payload, $placeholders, $messageId)
    {
        $pageSize = 'A4';
        $pageOrientation = 'portrait';

        if (array_key_exists('CompanyLogo', $placeholders)) {
            $placeholders['CompanyLogo'] = Helper::generateDataImage($placeholders['CompanyLogo']);
        }

        $isPdf = ($payload['letterEditorMode'] ?? '') === 'PDF';

        try {
            if ($isPdf) {
                $pdfBuffer = self::generateFromPdfTemplate($payload, $placeholders);
            } else {
                $html = self::replacePlaceholders($payload['emailTemplate'], $placeholders);
                $pdfBuffer = self::htmlToPdf($html, $pageSize, $pageOrientation);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $documentName = $payload['actionPayload']['documentName'] ?? '';
        $documentId = $payload['actionPayload']['documentId'] ?? '';

        $subject = self::replacePlaceholders($payload['subject'], $placeholders);

        $filename = preg_replace('/[^A-Za-z0-9 ]/', '', $subject).' - '.microtime(true).'.pdf';

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

    private static function replacePlaceholders(string $template, array $placeholders): string
    {
        return preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($placeholders) {
            return $placeholders[trim($matches[1])] ?? '';
        }, $template);
    }

    /**
     * Generate PDF by stamping placeholder values onto an existing PDF template
     * using FPDI. The source PDF is fetched from the email-builder-backend
     * (already normalized to v1.4 at upload time so FPDI can read it).
     */
    protected static function generateFromPdfTemplate(array $payload, array $placeholders): string
    {
        $pdfS3Key = $payload['pdfS3Key'] ?? '';
        $pdfPlaceholders = $payload['pdfPlaceholders'] ?? [];

        if (empty($pdfS3Key)) {
            throw new \Exception('PDF template S3 key is missing.');
        }

        $pdfContent = WorkflowEmailService::fetchPdfFile($pdfS3Key);

        return PdfStamper::stamp($pdfContent, $pdfPlaceholders, $placeholders);
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
