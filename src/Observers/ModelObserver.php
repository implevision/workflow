<?php

namespace Taurus\Workflow\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Taurus\Workflow\Events\InvokeMatchingWorkflowEvent;


class ModelObserver implements ShouldHandleEventsAfterCommit

{
    public function handle($model, $method)
    {
        //$updatedFields = $model->getDirty();
        try {
            $entity = $model->getKey();
            $entityAction = $method;
            $entityType =  get_class($model);
            event(new InvokeMatchingWorkflowEvent($entity, $entityAction, $entityType));
        } catch (\Exception $e) {
            \Log::info('WORKFLOW - Error dispatching matching workflow: ' . $e->getMessage());
        }
    }

    /**
     * Handle the "created" event.
     */
    public function created($model): void
    {
        $this->handle($model, 'CREATE');
    }

    /**
     * Handle the "updated" event.
     */
    public function updated($model): void
    {
        $this->handle($model, 'UPDATE');
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted($model): void
    {
        $this->handle($model, 'DELETE');
    }

    /**
     * Handle the "restored" event.
     */
    public function restored($model): void {}

    /**
     * Handle the "forceDeleted" event.
     */
    public function forceDeleted($model): void {}
}
