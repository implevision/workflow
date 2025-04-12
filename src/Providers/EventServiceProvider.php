<?php

namespace Taurus\Workflow\Providers;

use Taurus\Workflow\Events\JobWorkflowCreated;
use Taurus\Workflow\Events\JobWorkflowUpdated;
use Taurus\Workflow\Listeners\HandleJobWorkflowUpdate;
use Taurus\Workflow\Listeners\HandleJobWorkflowCreation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JobWorkflowCreated::class => [
            HandleJobWorkflowCreation::class,
        ],
        JobWorkflowUpdated::class => [
            HandleJobWorkflowUpdate::class,
        ],
    ];
}
