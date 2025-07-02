<?php

namespace Taurus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Taurus\Workflow\Services\WorkflowService;
use Illuminate\Support\Facades\Artisan;

class InvokeMatchingWorkflow extends Command
{
    protected $workflowService;
    public function __construct(WorkflowService $workflowService,)
    {
        $this->workflowService = $workflowService;
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:invoke-matching-workflow {--Entity=} {--EntityAction=} {--EntityType=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invoke the matching workflow';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $entityType = $this->option('EntityType');
        $entity = $this->option('Entity');
        $entityAction = $this->option('EntityAction');

        if (empty($entity) || empty($entityAction) || empty($entityType)) {
            $this->error('Entity and EntityAction  and EntityType options are required.');
            return 1;
        }

        $matchedWorkflow = $this->workflowService->getMatchingWorkflow($entityType, $entityAction, $entity);

        if (empty($matchedWorkflow)) {
            $this->info('No matching workflow found.');
            return 0;
        }

        foreach ($matchedWorkflow as $workflowId) {
            try {
                Artisan::call('taurus:dispatch-workflow', [
                    '--workflowId' => $workflowId
                ]);
            } catch (\Exception $e) {
                //TODO: WORKFLOW - Notify for errors
                $this->error('Error dispatching workflow: ' . $e->getMessage());
                $this->error('Error dispatching workflow: ' . $e->getFile());
                $this->error('Error dispatching workflow: ' . $e->getLine());
                return 1;
            }
        }
        $this->info('Matching workflow dispatched successfully.');
        return 0;
    }
}
