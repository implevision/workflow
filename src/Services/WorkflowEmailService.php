<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Http;

class WorkflowEmailService
{
    public static function getEmailInformation($id)
    {
        $response = Http::withHeaders(['x-client-key' => config('workflow.email_template_service_client_key'), 'X-Tenant' => getTenant()])
            ->acceptJson()
            ->get(config('workflow.email_template_service_url').'/api/email/template/get/'.$id);

        if ($response->successful()) {
            $response = $response->json();

            return $response;
        } else {
            throw new \Exception($response->body());
        }
    }

    /**
     * Fetch a PDF file from S3 via the email-builder backend.
     *
     * @param  string  $s3Key  The S3 key of the PDF file.
     * @return string Raw PDF binary content.
     *
     * @throws \Exception
     */
    public static function fetchPdfFile(string $s3Key): string
    {
        $response = Http::withHeaders([
            'x-client-key' => config('workflow.email_template_service_client_key'),
            'X-Tenant' => getTenant(),
        ])
            ->post(config('workflow.email_template_service_url').'/api/email/template/pdf/file', [
                's3Key' => $s3Key,
            ]);

        if ($response->successful()) {
            return $response->body();
        } else {
            throw new \Exception('Failed to fetch PDF file from email-builder service: '.$response->body());
        }
    }
}
