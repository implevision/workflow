<?php

namespace Taurus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Taurus\Workflow\Services\CheckUserCapabilities;

class InvokeUpcomingWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:invoke-upcoming-workflow {--self-test=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invoke the upcoming workflow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $selfTest = $this->option('self-test');
        if ($selfTest) {
            \Log::info("Self test mode enabled. No workflows will be dispatched.");
            $this->selfTest();
        } else {
            $this->info("Executing workflow...");
        }

        return 0;
    }

    private function selfTest()
    {
        $testDir = base_path('vendor/taurus/workflow/tests/invoke-upcoming-workflow');
        $jsonFiles = glob($testDir . '/*.json');
    }
}
