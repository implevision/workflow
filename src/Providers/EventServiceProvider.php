<?php

namespace Taurus\Workflow\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Taurus\Workflow\Events\JobWorkflowCreatedEvent;
use Taurus\Workflow\Events\JobWorkflowUpdatedEvent;
use Taurus\Workflow\Events\PostActionEvent;
use Taurus\Workflow\Listeners\HandleJobWorkflowCreatedEvent;
use Taurus\Workflow\Listeners\HandleJobWorkflowUpdatedEvent;
use Taurus\Workflow\Listeners\HandlePostActionEvent;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JobWorkflowCreatedEvent::class => [
            HandleJobWorkflowCreatedEvent::class,
        ],
        JobWorkflowUpdatedEvent::class => [
            HandleJobWorkflowUpdatedEvent::class,
        ],
        PostActionEvent::class => [
            HandlePostActionEvent::class,
        ],
    ];
}
