<?php

namespace Taurus\Workflow\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Taurus\Workflow\Data\WorkflowData;
use Taurus\Workflow\Http\Requests\WorkflowRequest;
use Taurus\Workflow\Services\WorkflowService;

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

    protected $workflowService;

    public function __construct()
    {
        parent::__construct();
        $this->workflowService = app(WorkflowService::class);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workflow = $this->option('workflow');

        \Log::info("WORKFLOW SEEDER - Starting seeding process for workflow: {$workflow}");

        $path = database_path("{$this->initialFilePath}/{$workflow}.json");
        $json = file_get_contents($path);
        if ($json === false) {
            \Log::error("WORKFLOW SEEDER - No file contents found in {$path} for workflow.");

            return 1;
        }

        $data = json_decode($json, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            \Log::error("WORKFLOW SEEDER - Invalid JSON in {$path} for workflow. Error: ".json_last_error_msg());
        }

        try {
            $validator = Validator::make($data, (new WorkflowRequest)->rules());
            if ($validator->fails()) {
                \Log::error('WORKFLOW SEEDER - Validation failed for workflow', $validator->errors()->all());

                return 1;
            }

            $workflowData = WorkflowData::fromArray($data);
            $this->workflowService->createWorkflow($workflowData);
        } catch (\Exception $e) {
            \Log::error("WORKFLOW SEEDER - Error while creating workflow: {$workflow}. Error: ".$e->getMessage());

            return 1;
        }

        if (empty($data['externalServices'])) {
            \Log::info("WORKFLOW SEEDER - Finishing seeding process for workflow: {$workflow}");

            return 1;
        }

        try {
            foreach ($data['externalServices'] as $key => $service) {
                switch ($key) {
                    case 'email':
                        $this->insertEmailData($service);
                        break;
                    default:
                        \Log::error("WORKFLOW SEEDER - No handler found for service type: {$key} in {$path} for workflow.");
                        break;
                }
            }
        } catch (\Exception $e) {
            \Log::error("WORKFLOW SEEDER - Error while inserting data for workflow: {$workflow}. Error: ".$e->getMessage());

            return 1;
        }

        \Log::info("WORKFLOW SEEDER - Finishing seeding process for workflow: {$workflow}");

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
                'X-Tenant' => getTenant(),
            ];
            $requestBody = [
                'subject' => $data['subject'],
                'html' => $emailTemplateContentAsString,
                'templateName' => $data['templateName'],
                'replyTo' => $data['replyTo'] ?? '',
                'senderName' => $data['senderName'] ?? '',
                'module' => $data['module'] ?? '',
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
