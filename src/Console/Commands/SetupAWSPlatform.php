<?php

namespace Taurus\Workflow\Console\Commands;

use Exception;
use Illuminate\Console\Command;

use Taurus\Workflow\Services\EventBridgeScheduler;

class SetupAWSPlatform extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:setup-aws-platform';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup AWS Platform';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Processing, please wait...");
        $this->setEventBridgeGroup();

        return 0;
    }

    private function setEventBridgeGroup()
    {
        $this->info("Setting up Event Bridge Group...");
        $this->info("Please wait...");

        $tenant = config('workflow.single_tenant');

        if (!$tenant && function_exists('tenant')) {
            $tenant = tenant();
        }

        if (!$tenant) {
            $this->error("Tenant not found.");
            return;
        }

        $groupName = 'taurus-workflow-' . $tenant;
        $tags = [
            [
                'Key' => 'service',
                'Value' => 'workflow'
            ],
            [
                'Key' => 'tenant',
                'Value' => $tenant
            ]
        ];

        try {
            //EventBridgeScheduler::createScheduleGroup($groupName, $tags);
        } catch (Exception $e) {
            $this->error("Error creating schedule group: " . $e->getMessage());
            return;
        }

        $this->info("Event Bridge Group setup completed.");
    }
}
