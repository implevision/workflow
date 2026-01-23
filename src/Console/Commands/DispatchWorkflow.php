<?php

namespace Taurus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Taurus\Workflow\Services\DispatchWorkflowService;

class DispatchWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:dispatch-workflow {--workflowId=} {--recordIdentifier=} {--data=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch workflow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workflowId = $this->option('workflowId');
        $recordIdentifier = $this->option('recordIdentifier', 0);
        $data = $this->option('data');
        $data = $data ? json_decode($data, true) : [];

        if (! $workflowId) {
            $this->error('The --workflowId option is required.');

            return 1; // Return a non-zero status code to indicate failure
        }

        $this->info("Workflow Id provided: $workflowId");

        setRunningWorkflowId($workflowId);
        setRecordIdentifierForRunningWorkflow($recordIdentifier);

        try {
            \Log::info('WORKFLOW - Dispatching workflow with ID '.$workflowId);
            $recordIdentifier ? \Log::info('WORKFLOW - Dispatching workflow with record identifier '.$recordIdentifier) : null;

            $workflow = new DispatchWorkflowService($workflowId, $recordIdentifier, $data);
            $workflow->dispatch();
        } catch (\Exception $e) {
            $errorMessage = "WORKFLOW - Error dispatching workflow with ID $workflowId: ".$e->getMessage();
            \Log::error($errorMessage);
            $this->error($errorMessage);

            return 1; // Return a non-zero status code to indicate failure
        }
    }
}
