<?php

namespace Taurus\Workflow\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

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

    protected $initialFilePath = 'seeders/workflow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workflow = $this->option('workflow');

        $path = database_path("{$this->initialFilePath}/{$workflow}.json");
        $json = file_get_contents($path);
        if ($json === false) {
            \Log::error("WORKFLOW - No file contents found in {$path} for workflow.");

            return 0;
        }

        $data = json_decode($json, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            \Log::error("WORKFLOW - Invalid JSON in {$path} for workflow. Error: ".json_last_error_msg());
        }

        if (empty($data['externalServices'])) {
            return 0;
        }

        try {
            foreach ($data['externalServices'] as $key => $service) {
                switch ($key) {
                    case 'email':
                        $this->insertEmailData($service['email']);
                        break;
                    default:
                        \Log::error("WORKFLOW - No handler found for service type: {$key} in {$path} for workflow.");
                        break;
                }
            }
        } catch (\Exception $e) {
            \Log::error("WORKFLOW - Error while inserting data for workflow: {$workflow}. Error: ".$e->getMessage());
        }

        return 0;
    }

    private function insertEmailData($data)
    {
        $emailTemplateFilePath = database_path("{$this->initialFilePath}/{$data['filePath']}");
        $emailTemplateContentAsString = file_get_contents($emailTemplateFilePath);

        $client = new Client;

        try {
            $headers = [
                'x-client-key' => config('workflow.email_template_service_client_key'),
                'X-Tenant' => tenant('id'),
            ];
            $requestBody = [
                'subject' => $data['subject'],
                'html' => $emailTemplateContentAsString,
                'templateName' => $data['templateName'],
            ];

            $response = $client->request(
                'post',
                // TODO - replace with config url
                config('workflow.email_template_service_url').'/api/email/template/save',
                [
                    'headers' => $headers,
                    'json' => $requestBody,
                ]
            );
            $responseBody = $response->getBody()->getContents();
            $responseBody = is_array($responseBody) ? $responseBody : json_decode($responseBody, true);

            if ($responseBody['status'] !== true) {
                throw new Exception($response->getBody());
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
