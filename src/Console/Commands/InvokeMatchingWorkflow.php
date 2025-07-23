<?php

namespace Taurus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Taurus\Workflow\Services\WorkflowService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

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
            $errorMessage = 'WORKFLOW - Entity, EntityAction and EntityType are required.';
            Log::error($errorMessage);
            $this->error($errorMessage);
            return 1;
        }

        try {
            $matchedWorkflow = $this->workflowService->getMatchingWorkflow($entityType, $entityAction, $entity);
        } catch (\Exception $e) {
            $errorMessage = 'WORKFLOW - Error finding match workflow for EntityType: ' . $entityType . ', EntityAction: ' . $entityAction . ', Entity: ' . $entity . ': ' . $e->getMessage();
            Log::error($errorMessage);
            $this->error($errorMessage);
            return 1;
        }

        if (empty($matchedWorkflow)) {
            $message = 'WORKFLOW - No matching workflow found for EntityType: ' . $entityType . ', EntityAction: ' . $entityAction . ', Entity: ' . $entity;
            Log::info($message);
            $this->info($message);
            return 0;
        }

        foreach ($matchedWorkflow as $workflowId) {
            $message = 'WORKFLOW - Matched Workflow found with ID: ' . $workflowId . ' for EntityType: ' . $entityType . ', EntityAction: ' . $entityAction . ', Entity: ' . $entity;
            Log::info($message);
            $this->info($message);

            try {
                $command = gitCommandToDispatchWorkflow($workflowId, $entity);
                Artisan::call($command['command'], $command['options']);
            } catch (\Exception $e) {
                $errorMessage = 'WORKFLOW - Error dispatching workflow with ID ' . $workflowId . ': ' . $e->getMessage();
                Log::error($errorMessage);
                $this->error($errorMessage);
                return 1;
            }
        }

        $message = 'WORKFLOW - Matching workflow dispatched successfully. for EntityType: ' . $entityType . ', EntityAction: ' . $entityAction . ', Entity: ' . $entity;
        Log::info($message);
        $this->info($message);
        return 0;
    }
}
