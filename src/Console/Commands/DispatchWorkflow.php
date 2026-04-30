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
    protected $signature = 'taurus:dispatch-workflow {--workflowId=} {--recordIdentifier=} {--data=} {--appendPlaceHolders=} {--page=1}';

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
        setWorkflowDBConnection();
        $workflowId = $this->option('workflowId');
        $recordIdentifier = $this->option('recordIdentifier') ?? 0;
        $data = $this->option('data');
        $data = $data ? json_decode($data, true) : [];
        $appendPlaceHolders = $this->option('appendPlaceHolders');
        $appendPlaceHolders = $appendPlaceHolders ? json_decode($appendPlaceHolders, true) : [];
        $page = (int) ($this->option('page') ?? 1);

        if (config('app.env') != 'production' && $page > 3) {
            $this->info("Page $page exceeds the allowed limit of 3 pages. Dispatch aborted.");
            return 0;
        }

        if (! $workflowId) {
            $this->error('The --workflowId option is required.');

            return 1; // Return a non-zero status code to indicate failure
        }

        $this->info("Workflow Id provided: $workflowId");

        setRunningWorkflowId($workflowId);
        setRecordIdentifierForRunningWorkflow($recordIdentifier);

        try {
            \Log::info('WORKFLOW - Dispatching workflow with ID '.$workflowId);
            \Log::info("WORKFLOW - Page: $page");
            $recordIdentifier ? \Log::info('WORKFLOW - Dispatching workflow with record identifier '.$recordIdentifier) : null;

            $workflow = new DispatchWorkflowService($workflowId, $recordIdentifier, $data, $appendPlaceHolders, $page);
            $workflow->dispatch();
        } catch (\Exception $e) {
            $errorMessage = "WORKFLOW - Error dispatching workflow with ID $workflowId: ".$e->getMessage();
            \Log::error($errorMessage);
            $this->error($errorMessage);

            return 1; // Return a non-zero status code to indicate failure
        }
    }
}
