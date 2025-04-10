<?php

namespace Taurus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Taurus\Workflow\Services\Workflow;
use Taurus\Workflow\Repositories\Contracts\JobWorkflowRepository;

class DispatchWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:dispatch-workflow {--workflowId=}';

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

        if (!$workflowId) {
            $this->error("The --workflowId option is required.");
            return 1; // Return a non-zero status code to indicate failure
        }

        $this->info("Workflow Id provided: $workflowId");

        $workflow = new Workflow($workflowId);



        /*$strategy = $workflow->getExecutionStrategy();

        if ($strategy == 'batch') {
            $this->info("Executing workflow in batch mode");
        } else {
            $this->info("Executing workflow in sequential mode");
        }*/
        $workflow->dispatch();
    }
}
