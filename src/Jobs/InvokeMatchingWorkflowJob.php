<?php

namespace Taurus\Workflow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class InvokeMatchingWorkflowJob implements ShouldQueue
{
    use Queueable;

    public int $entity;

    public string $entityAction;

    public string $entityType;

    public array $data;

    public array $appendPlaceHolders;

    public array $updatedFields;

    /**
     * Create a new event instance.
     */
    public function __construct(int $entity, string $entityAction, string $entityType, array $data = [], array $appendPlaceHolders = [], array $updatedFields = [])
    {
        $this->entity = $entity;
        $this->entityAction = $entityAction;
        $this->entityType = $entityType;
        $this->data = $data;
        $this->appendPlaceHolders = $appendPlaceHolders;
        $this->updatedFields = $updatedFields;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        setWorkflowDBConnection();
        try {
            // Log the event details
            Log::info('WORKFLOW - Handling job workflow created event', [
                'entity' => $this->entity,
                'action' => $this->entityAction,
                'type' => $this->entityType,
                'data' => $this->data,
                'appendPlaceHolders' => $this->appendPlaceHolders,
                'updatedFields' => $this->updatedFields,
            ]);

            $workflowService = app()->make('Taurus\Workflow\Services\WorkflowService');
            $parentClassService = $workflowService->getParentClassService();
            $overrideEntityParams = $parentClassService->getParentEntity($this->entityType, $this->entity);

            if (is_array($overrideEntityParams)) {
                $this->entity = $overrideEntityParams['entity'] ?? $this->entity;
                $this->entityType = $overrideEntityParams['entityType'] ?? $this->entityType;
            }

            $command = getCommandToDispatchMatchingWorkflow($this->entity, $this->entityAction, $this->entityType, $this->data, $this->appendPlaceHolders, $this->updatedFields);
            try {
                Artisan::call($command['command'], $command['options']);
            } catch (\Exception $e) {
                Log::info('WORKFLOW - Error dispatching matching workflow: '.$e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('WORKFLOW - '.$e->getMessage());
        }
    }
}
