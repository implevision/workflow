<?php

namespace Taurus\Workflow\Providers;

use Taurus\Workflow\Events\JobWorkflowCreatedEvent;
use Taurus\Workflow\Events\JobWorkflowUpdatedEvent;
use Taurus\Workflow\Events\PostActionEvent;
use Taurus\Workflow\Listeners\HandleJobWorkflowUpdate;
use Taurus\Workflow\Listeners\HandleJobWorkflowCreation;
use Taurus\Workflow\Listeners\HandlePostActionEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JobWorkflowCreatedEvent::class => [
            HandleJobWorkflowCreation::class,
        ],
        JobWorkflowUpdatedEvent::class => [
            HandleJobWorkflowUpdate::class,
        ],
        PostActionEvent::class => [
            HandlePostActionEvent::class,
        ],
    ];
}
