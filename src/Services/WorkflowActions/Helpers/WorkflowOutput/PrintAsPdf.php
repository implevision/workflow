<?php

namespace Taurus\Workflow\Services\WorkflowActions\Helpers\WorkflowOutput;

use Dompdf\Dompdf;
use Taurus\Workflow\Events\JobWorkflowUpdatedEvent;
use Taurus\Workflow\Services\AWS\S3;
use Taurus\Workflow\Services\WorkflowEmailService;

class PrintAsPdf
{
    /**
     * Generate a PDF from the template, upload to S3, and save the path in job workflow response.
     */
    public function generate(int $jobWorkflowId, array $data, array $templateInformation): void
    {
        $placeholders = ! empty($data[0]) ? $data[0] : [];

        $isPdf = ($templateInformation['letterEditorMode'] ?? '') === 'PDF';

        if ($isPdf) {
            $pdfBuffer = $this->generateFromPdfTemplate($templateInformation, $placeholders);
        } else {
            $html = $templateInformation['html'] ?? '';
            $html = $this->replacePlaceholders($html, $placeholders);
            $pdfBuffer = $this->htmlToPdf($html);
        }

        $filename = 'workflow_output_' . microtime(true) . '.pdf';
        $s3Path = sprintf('%s/%s/%s/%s/workflowOutput/%s', getTenant(), date('Y'), date('m'), date('d'), $filename);
        $bucketName = config('workflow.bucket_to_save_email_letters', config('filesystems.disks.s3.bucket'));

        S3::uploadFile($bucketName, $s3Path, $pdfBuffer);

        \Log::info('WORKFLOW - PDF uploaded to S3', ['s3Path' => $s3Path]);

        event(new JobWorkflowUpdatedEvent($jobWorkflowId, [
            'total_no_of_records_to_execute' => 1,
        ]));

        event(new JobWorkflowUpdatedEvent($jobWorkflowId, [
            'response' => [
                'PRINT_AS_PDF' => [
                    's3Path' => $s3Path,
                ],
            ],
        ]));

        event(new JobWorkflowUpdatedEvent($jobWorkflowId, [
            'total_no_of_records_executed' => 1,
        ]));
    }

    /**
     * Generate PDF by stamping placeholder values onto an existing PDF template
     * using FPDI locally. The source PDF is fetched from the email-builder backend
     * (already normalized to v1.4 at upload time so FPDI can read it).
     */
    protected function generateFromPdfTemplate(array $templateInformation, array $placeholders): string
    {
        $pdfS3Key = $templateInformation['pdfS3Key'] ?? '';
        $pdfPlaceholders = $templateInformation['pdfPlaceholders'] ?? [];

        if (empty($pdfS3Key)) {
            throw new \Exception('PDF template S3 key is missing.');
        }

        $pdfContent = WorkflowEmailService::fetchPdfFile($pdfS3Key);

        return PdfStamper::stamp($pdfContent, $pdfPlaceholders, $placeholders);
    }

    protected function replacePlaceholders(string $content, array $placeholders): string
    {
        return preg_replace_callback('/{{\s*(.*?)\s*}}/', function ($matches) use ($placeholders) {
            $key = trim($matches[1]);

            return $placeholders[$key] ?? '';
        }, $content);
    }

    protected function htmlToPdf(string $html, string $pageSize = 'A4', string $pageOrientation = 'portrait'): string
    {
        $domPdf = new Dompdf;
        $domPdf->loadHtml($html);
        $domPdf->setPaper($pageSize, $pageOrientation);
        $domPdf->render();

        return $domPdf->output();
    }
}
