<?php

namespace Taurus\Workflow\Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Taurus\Workflow\Repositories\Contracts\WorkflowLogRepositoryInterface;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;
use Taurus\Workflow\Services\WorkflowActions\CreateRecordAction;
use Taurus\Workflow\Services\WorkflowActions\EmailAction;
use Taurus\Workflow\Services\WorkflowActions\WebhookAction;
use Taurus\Workflow\Services\WorkflowActions\WorkflowOutputAction;
use Taurus\Workflow\Services\WorkflowService;
use Taurus\Workflow\Tests\TestCase;

trait InteractsWithDispatch
{
    protected FakeWorkflowService $workflowService;

    protected FakeJobWorkflowRepository $jobWorkflowRepo;

    protected FakeWorkflowLogRepository $workflowLogRepo;

    /** @var FakeAction[] */
    protected array $actions = [];

    protected function fakeDispatchCollaborators(): void
    {
        $this->app->instance(WorkflowService::class, $this->workflowService = new FakeWorkflowService);
        $this->app->instance(JobWorkflowRepository::class, $this->jobWorkflowRepo = new FakeJobWorkflowRepository);
        $this->app->instance(WorkflowLogRepositoryInterface::class, $this->workflowLogRepo = new FakeWorkflowLogRepository);
        $this->app->bind(GraphQLSchemaBuilderService::class, fn ($app, $params) => new FakeSchemaBuilder($params['fieldMapping']));

        foreach ([EmailAction::class, WebhookAction::class, WorkflowOutputAction::class, CreateRecordAction::class] as $actionClass) {
            $this->app->bind($actionClass, fn ($app, $params) => $this->actions[] = new FakeAction($params['action'], $params['payload']));
        }
    }

    // Each argument is one response: an array is the GraphQL `data`, anything else is returned as is.
    protected function fakeGraphQL(mixed ...$responses): void
    {
        $sequence = Http::sequence();

        foreach ($responses as $response) {
            is_array($response) ? $sequence->push(['data' => $response]) : $sequence->pushResponse($response);
        }

        Http::fake([TestCase::GRAPHQL_ENDPOINT => $sequence]);
    }

    protected function graphQLRecords(array $records, bool $hasMorePages = false): array
    {
        return [FakeModuleSchema::QUERY_NAME => ['data' => $records, 'paginatorInfo' => ['hasMorePages' => $hasMorePages]]];
    }

    protected function graphQLQueriesSent(): array
    {
        return Http::recorded(fn (Request $request) => $request->url() === TestCase::GRAPHQL_ENDPOINT)
            ->map(fn (array $pair) => $pair[0]['query'])
            ->values()
            ->all();
    }

    protected function executedActions(): array
    {
        return array_values(array_filter($this->actions, fn (FakeAction $action) => $action->executed));
    }

    protected function loggedErrorTypes(): array
    {
        return array_column($this->workflowService->errors, 'errorType');
    }
}
