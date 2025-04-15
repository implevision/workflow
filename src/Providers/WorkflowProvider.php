<?php

namespace Taurus\Workflow\Providers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Taurus\Workflow\Repositories\Eloquent\WorkflowRepository;
use Taurus\Workflow\Repositories\Eloquent\WorkflowActionRepository;
use Taurus\Workflow\Repositories\Eloquent\WorkflowConditionRepository;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Taurus\Workflow\Repositories\Contracts\WorkflowActionRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\JobWorkflowRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowConditionRepositoryInterface;
use Taurus\Workflow\Console\Commands\DispatchWorkflow;
use Taurus\Workflow\Console\Commands\HealthCheck;

class WorkflowProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/workflow.php' => config_path('workflow.php'),
        ]);


        // PUT this file manually in the database/migrations folder of INFRASTRUCTURE
        /*$this->publishesMigrations([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ]);*/
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/workflow.php',
            'workflow'
        );

        $this->commands([
            DispatchWorkflow::class,
            HealthCheck::class
        ]);

        $repositories = [
            WorkflowRepositoryInterface::class => WorkflowRepository::class,
            WorkflowConditionRepositoryInterface::class => WorkflowConditionRepository::class,
            WorkflowActionRepositoryInterface::class => WorkflowActionRepository::class,
            JobWorkflowRepositoryInterface::class => JobWorkflowRepository::class,
        ];

        foreach ($repositories as $interface => $repository) {
            try {
                $this->app->bind($interface, $repository);
            } catch (BindingResolutionException $e) {
                Log::error('Binding resolution error in RepositoryServiceProvider:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}
