<?php

namespace Taurus\Workflow\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Taurus\Workflow\Providers\EventServiceProvider;
use Taurus\Workflow\Providers\WorkflowProvider;

abstract class TestCase extends OrchestraTestCase
{
    public const GRAPHQL_ENDPOINT = 'http://graphql.test/graphql';

    protected function getPackageProviders($app): array
    {
        return [LaravelDataServiceProvider::class, WorkflowProvider::class, EventServiceProvider::class];
    }

    // Unit tests never touch a database: any query fails on the unconfigured connection.
    protected function defineEnvironment($app): void
    {
        $app['config']->set([
            'database.default' => 'none',
            'app.env' => 'testing',
            'workflow.graphql.endpoint' => self::GRAPHQL_ENDPOINT,
            'workflow.allowed_receiver.email' => ['allowed@example.com'],
            'workflow.allowed_receiver.ends_with' => ['@allowed.test'],
            'workflow.send_all_workflow_email_to' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Log::spy();
    }
}
