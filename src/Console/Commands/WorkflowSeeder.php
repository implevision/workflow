<?php

namespace Taurus\Workflow\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use GuzzleHttp\Client;

class WorkflowSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:seed-workflow {--workflow=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed all the required details for a workflow to work.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workflow = $this->option('workflow');
        $initialFilePath = 'seeders/workflow/';

        $path = database_path("$initialFilePath{$workflow}.json");
        $json = file_get_contents($path);
        if ($json === false) {
            throw new Exception("No file contents found in $workflow for workflow.");
            return 0;
        }
        $configData = json_decode($json, true);

        $emailTemplateFilePath = database_path("$initialFilePath{$configData['filePath']}");
        $emailTemplateContentAsString = file_get_contents($emailTemplateFilePath);
        if ($json === false) {
            throw new Exception("No file contents found in $emailTemplateFilePath for email template.");
            return 0;
        }

        $client = new Client();

        try {
            $headers = [
                "x-client-key" => config("workflow.email_template_service_client_key"),
                "X-Tenant" => tenant("id"),
            ];
            $requestBody = [
                "subject" => $configData["subject"],
                "html" => $emailTemplateContentAsString,
                "templateName" => $configData["templateName"],
            ];

            $response = $client->request(
                'post',
                // TODO - replace with config url
                "https://services.uat2.odysseynext.com/email-builder-backend/api/email/template/save",
                [
                    'headers' => $headers,
                    "json" => $requestBody
                ]
            );
            $responseBody = $response->getBody()->getContents();
            $responseBody = is_array($responseBody) ? $responseBody : json_decode($responseBody, true);

            if ($responseBody["status"] !== true) {
                throw new Exception($response->getBody());
            }
        } catch (Exception $e) {
            throw new Exception($e->getResponse()->getBody());
        }

        return 0;
    }
}
