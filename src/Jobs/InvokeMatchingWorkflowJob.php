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

    /**
     * Create a new event instance.
     */
    public function __construct(int $entity, string $entityAction, string $entityType, array $data = [], array $appendPlaceHolders = [])
    {
        $this->entity = $entity;
        $this->entityAction = $entityAction;
        $this->entityType = $entityType;
        $this->data = $data;
        $this->appendPlaceHolders = $appendPlaceHolders;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Log the event details
            Log::info('WORKFLOW - Handling job workflow created event', [
                'entity' => $this->entity,
                'action' => $this->entityAction,
                'type' => $this->entityType,
                'data' => $this->data,
                'appendPlaceHolders' => $this->appendPlaceHolders,
            ]);

            $command = getCommandToDispatchMatchingWorkflow($this->entity, $this->entityAction, $this->entityType, $this->data, $this->appendPlaceHolders);
            try {
                Artisan::call($command['command'], $command['options']);
            } catch (\Exception $e) {
                Log::info('WORKFLOW - Error dispatching matching workflow: '.$e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('WORKFLOW - '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }
}
