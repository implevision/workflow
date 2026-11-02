<?php

namespace Taurus\Workflow\Tests\Services;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Taurus\Workflow\Models\Workflow;
use Taurus\Workflow\Repositories\Eloquent\WorkflowRepository;
use Taurus\Workflow\Services\DispatchWorkflowService;
use Taurus\Workflow\Tests\Support\InteractsWithDispatch;
use Taurus\Workflow\Tests\Support\WorkflowFactory;
use Taurus\Workflow\Tests\TestCase;

class DispatchWorkflowServiceTest extends TestCase
{
    use InteractsWithDispatch;

    private const RECORD_ID = 42;

    private const RECORD = ['policyNumber' => 'P-100', 'firstName' => 'Ann', 'agentEmail' => 'agent@allowed.test'];

    private const EMPTY_RECORD = ['policyNumber' => null, 'firstName' => '', 'agentEmail' => null];

    private const STATUS_IS_ACTIVE = '{ column: STATUS, operator: EQ, value: "ACTIVE" }';

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDispatchCollaborators();
    }

    private function givenWorkflow(array ...$conditions): void
    {
        $this->workflowService->workflow = WorkflowFactory::workflow($conditions);
    }

    private function dispatchWorkflow(int|string $recordIdentifier = 0, array $data = [], array $appendPlaceHolders = [], ?string $referenceId = null, int $page = 0, int $workflowId = WorkflowFactory::ID): bool
    {
        return (new DispatchWorkflowService($workflowId, $recordIdentifier, $data, $appendPlaceHolders, $referenceId, $page))->dispatch();
    }

    // Testbench's console kernel is final, so Artisan::shouldReceive() cannot be used.
    private function mockArtisan(): MockInterface
    {
        $kernel = \Mockery::mock(ConsoleKernel::class);
        Artisan::swap($kernel);

        return $kernel;
    }

    private function statusIsActive(): array
    {
        return WorkflowFactory::rule('@status', 'EQ', 'ACTIVE');
    }

    private function loggedActionTypes(): array
    {
        return array_column($this->workflowLogRepo->created, 'action_type');
    }

    public function test_returns_false_for_workflow_id_zero(): void
    {
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()]));

        $this->assertFalse($this->dispatchWorkflow(workflowId: 0));
        $this->assertSame([], $this->jobWorkflowRepo->created);
    }

    public function test_returns_false_when_the_workflow_cannot_be_loaded(): void
    {
        $this->assertFalse($this->dispatchWorkflow());
        $this->assertSame([], $this->jobWorkflowRepo->created);
    }

    public function test_returns_false_for_an_inactive_workflow_without_creating_a_job(): void
    {
        $this->workflowService->workflow = WorkflowFactory::workflow([WorkflowFactory::condition([WorkflowFactory::emailAction()])], isActive: false);

        $this->assertFalse($this->dispatchWorkflow(self::RECORD_ID));
        $this->assertSame([], $this->jobWorkflowRepo->created);
        $this->assertSame([], $this->graphQLQueriesSent());
    }

    public function test_an_inactive_with_in_workflow_is_still_rescheduled(): void
    {
        $this->workflowService->workflow = WorkflowFactory::workflow(isActive: false, dateTimeInfo: ['executionEventIncident' => 'WITH_IN']);
        $workflowRepo = \Mockery::mock(WorkflowRepository::class);
        $workflowRepo->shouldReceive('getById')->with(WorkflowFactory::ID)->andReturn((new Workflow)->forceFill(['id' => WorkflowFactory::ID]));
        $this->app->instance(WorkflowRepository::class, $workflowRepo);

        $this->assertFalse($this->dispatchWorkflow());
        $this->assertSame(WorkflowFactory::ID, $this->workflowService->scheduledWorkflows[0][0]['id']);
    }

    public function test_runs_the_action_for_the_targeted_record(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()]));

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID, referenceId: 'ref-1'));

        $this->assertSame([WorkflowFactory::ID, 'ref-1'], [$this->jobWorkflowRepo->created[0]['workflow_id'], $this->jobWorkflowRepo->created[0]['reference_id']]);
        $this->assertSame(['EMAIL', self::RECORD_ID, 1], [
            $this->workflowLogRepo->created[0]['action_type'],
            $this->workflowLogRepo->created[0]['record_identifier'],
            $this->workflowLogRepo->created[0]['job_workflow_id'],
        ]);
        $this->assertSame([[WorkflowFactory::ID, 1]], $this->workflowLogRepo->completed);

        [$query] = $this->graphQLQueriesSent();
        $this->assertStringContainsString('where: { column: POLICY_ID, operator: EQ, value: "42" }', $query);
        foreach (['firstName', 'policyNumber', 'agentEmail'] as $field) {
            $this->assertStringContainsString($field, $query);
        }

        [$action] = $this->executedActions();
        $this->assertSame([WorkflowFactory::ID, 1, self::RECORD_ID, 'policy', ''], [
            $action->getWorkflowId(),
            $action->getJobWorkflowId(),
            $action->getRecordIdentifier(),
            $action->getModule(),
            $action->getFeedFile(),
        ]);
        $this->assertSame([[
            'FirstName' => 'Ann',
            'PolicyNumber' => 'P-100',
            'AgentEmail' => 'agent@allowed.test',
            'hasPriorDataForWorkflow' => true,
            'email' => ['agent@allowed.test'],
        ]], $action->getData());
        $this->assertSame('policy', getModuleForCurrentWorkflow());
    }

    public function test_a_certain_condition_without_a_record_targets_every_matching_record(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD, ['firstName' => 'Ben', 'policyNumber' => 'P-200', 'agentEmail' => 'ben@allowed.test']]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()], 'CERTAIN', $this->statusIsActive()));

        $this->assertTrue($this->dispatchWorkflow());

        $this->assertStringContainsString('where: { AND: ['.self::STATUS_IS_ACTIVE.'] } page: 1', $this->graphQLQueriesSent()[0]);
        $this->assertSame(['Ann', 'Ben'], array_column($this->executedActions()[0]->getData(), 'FirstName'));
    }

    public function test_known_bug_all_condition_without_record_or_effective_query_cannot_build_a_query(): void
    {
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()]));

        $this->assertTrue($this->dispatchWorkflow());

        $this->assertSame([], $this->graphQLQueriesSent());
        $this->assertSame([], $this->executedActions());
        $this->assertSame(['GRAPHQL_ERROR'], $this->loggedErrorTypes());
    }

    public function test_manually_invoked_runs_use_the_supplied_data_without_querying_graphql(): void
    {
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()], 'CERTAIN', $this->statusIsActive()));
        $data = ['FirstName' => 'Manual', 'AgentEmail' => 'manual@allowed.test'];

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID, $data));

        $this->assertSame([], $this->graphQLQueriesSent());
        $this->assertSame([[...$data, 'hasPriorDataForWorkflow' => true, 'email' => ['manual@allowed.test']]], $this->executedActions()[0]->getData());
    }

    public function test_inactive_conditions_are_skipped(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(
            WorkflowFactory::condition([WorkflowFactory::workflowOutputAction()], status: false),
            WorkflowFactory::condition([WorkflowFactory::emailAction()]),
        );

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID));

        $this->assertSame(['EMAIL'], $this->loggedActionTypes());
    }

    public function test_certain_condition_rules_are_joined_onto_the_record_query(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()], 'CERTAIN', $this->statusIsActive()));

        $this->dispatchWorkflow(self::RECORD_ID);

        $this->assertStringContainsString(
            'where: { column: POLICY_ID, operator: EQ, value: "42", AND: ['.self::STATUS_IS_ACTIVE.'] }',
            $this->graphQLQueriesSent()[0]
        );
    }

    public function test_certain_condition_rules_carry_over_into_later_conditions(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]), $this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(
            WorkflowFactory::condition([WorkflowFactory::emailAction()], 'CERTAIN', $this->statusIsActive()),
            WorkflowFactory::condition([WorkflowFactory::emailAction()], 'CERTAIN', WorkflowFactory::rule('@state', 'EQ', 'TX')),
        );

        $this->dispatchWorkflow(self::RECORD_ID);

        [$first, $second] = $this->graphQLQueriesSent();
        $this->assertStringNotContainsString('STATE', $first);
        $this->assertStringContainsString(self::STATUS_IS_ACTIVE, $second);
        $this->assertStringContainsString('{ column: STATE, operator: EQ, value: "TX" }', $second);
    }

    public function test_known_bug_a_condition_keeps_only_one_action_per_action_type(): void
    {
        // WorkflowConditionData::fromArray() keys instanceActions by actionType.
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(WorkflowFactory::condition([
            WorkflowFactory::emailAction(['id' => 'tpl-first']),
            WorkflowFactory::emailAction(['id' => 'tpl-second']),
        ]));

        $this->dispatchWorkflow(self::RECORD_ID);

        $this->assertCount(1, $this->executedActions());
        $this->assertSame('tpl-second', $this->executedActions()[0]->getPayload()['id']);
    }

    public function test_on_date_time_follow_up_workflows_filter_by_the_effective_action_query(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->workflowService->effectiveActionQuery = ['column' => 'EXPIRY_DATE', 'operator' => 'EQ', 'value' => '2026-01-31'];
        $this->workflowService->workflow = WorkflowFactory::workflow(
            [WorkflowFactory::condition([WorkflowFactory::emailAction()])],
            effectiveAction: 'ON_DATE_TIME',
            dateTimeInfo: ['executionFrequency' => '30', 'executionFrequencyType' => 'DAY', 'executionEventIncident' => 'BEFORE', 'executionEvent' => 'EXPIRY_DATE'],
        );

        $this->dispatchWorkflow();

        $this->assertSame([['policy', '30', 'DAY', 'BEFORE', 'EXPIRY_DATE']], $this->workflowService->effectiveActionQueryCalls);
        $this->assertStringContainsString('where: { column: EXPIRY_DATE, operator: EQ, value: "2026-01-31" }', $this->graphQLQueriesSent()[0]);
        $this->assertCount(1, $this->executedActions());
    }

    public function test_effective_action_query_is_joined_onto_the_record_query(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->workflowService->effectiveActionQuery = ['column' => 'EXPIRY_DATE', 'operator' => 'EQ', 'value' => '2026-01-31'];
        $this->workflowService->workflow = WorkflowFactory::workflow([WorkflowFactory::condition([WorkflowFactory::emailAction()])], effectiveAction: 'ON_DATE_TIME');

        $this->dispatchWorkflow(self::RECORD_ID);

        $this->assertStringContainsString(
            'where: { column: POLICY_ID, operator: EQ, value: "42", AND: [{ column: EXPIRY_DATE, operator: EQ, value: "2026-01-31" }] }',
            $this->graphQLQueriesSent()[0]
        );
    }

    public function test_certain_date_time_workflows_do_not_use_the_effective_action_query(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->workflowService->workflow = WorkflowFactory::workflow(
            [WorkflowFactory::condition([WorkflowFactory::emailAction()])],
            effectiveAction: 'ON_DATE_TIME',
            dateTimeInfo: ['certainDateTime' => true],
        );

        $this->dispatchWorkflow(self::RECORD_ID);

        $this->assertSame([], $this->workflowService->effectiveActionQueryCalls);
    }

    public function test_action_initialisation_failure_skips_the_rest_of_the_condition(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(
            WorkflowFactory::condition([WorkflowFactory::emailAction(['failOnHandle' => true]), WorkflowFactory::workflowOutputAction()]),
            WorkflowFactory::condition([WorkflowFactory::emailAction()]),
        );

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID));

        $this->assertSame(['EMAIL', 'EMAIL'], $this->loggedActionTypes());
        $this->assertSame(['ERROR_INITIATING_ACTION'], $this->loggedErrorTypes());
        $this->assertCount(1, $this->executedActions());
        $this->assertCount(1, $this->workflowLogRepo->completed);
    }

    public function test_a_skipped_condition_is_not_marked_completed(): void
    {
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction(['failOnHandle' => true])]));

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID));

        $this->assertSame([], $this->workflowLogRepo->completed);
    }

    public function test_a_graphql_failure_abandons_only_that_action(): void
    {
        $this->fakeGraphQL(Http::response('upstream down', 502), $this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction(), WorkflowFactory::workflowOutputAction()]));

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID));

        $this->assertSame(['WORKFLOW_OUTPUT'], array_map(fn ($action) => $action->getCurrentAction(), $this->executedActions()));
        $this->assertCount(1, $this->workflowLogRepo->completed);
        $this->assertSame(['GRAPHQL_ERROR'], $this->loggedErrorTypes());
    }

    public function test_an_execute_failure_is_logged_and_the_next_action_still_runs(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]), $this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction(['failOnExecute' => true]), WorkflowFactory::workflowOutputAction()]));

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID));

        $this->assertCount(2, $this->executedActions());
        Log::shouldHaveReceived('error')->with('WORKFLOW - Error while executing action - EMAIL : execute failed');
    }

    public function test_an_action_without_its_mandatory_data_is_not_executed(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([['firstName' => 'Ann', 'agentEmail' => 'agent@allowed.test']]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction(['mandatoryPlaceholders' => ['PolicyNumber']])]));

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID));

        $this->assertSame([], $this->executedActions());
        $this->assertSame(['MISSING_MANDATE_DATA'], $this->loggedErrorTypes());
    }

    public function test_an_empty_graphql_response_abandons_the_action(): void
    {
        $this->fakeGraphQL([]);
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()]));

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID));

        $this->assertSame([], $this->executedActions());
        Log::shouldHaveReceived('warning')->with('WORKFLOW - GraphQL unable to fetch the data');
    }

    public function test_a_targeted_record_without_data_stops_the_whole_run(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::EMPTY_RECORD]));
        $this->givenWorkflow(
            WorkflowFactory::condition([WorkflowFactory::emailAction(), WorkflowFactory::workflowOutputAction()]),
            WorkflowFactory::condition([WorkflowFactory::emailAction()]),
        );

        $this->assertTrue($this->dispatchWorkflow(self::RECORD_ID));

        $this->assertSame(['EMAIL'], $this->loggedActionTypes());
        $this->assertSame([], $this->workflowLogRepo->completed);
        $this->assertSame([], $this->executedActions());
    }

    public function test_record_query_build_failure_propagates_after_the_job_is_created(): void
    {
        $this->workflowService->recordIdentifierQuery = new \Exception('module offline');
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()]));

        try {
            $this->dispatchWorkflow(self::RECORD_ID);
            $this->fail('Expected the record-identifier query failure to propagate');
        } catch (\Exception $e) {
            $this->assertSame('Error while creating GraphQL query for record identifier. module offline', $e->getMessage());
        }

        $this->assertCount(1, $this->jobWorkflowRepo->created);
        $this->assertSame([], $this->workflowLogRepo->created);
    }

    public function test_dispatches_the_next_page_once_when_more_pages_exist(): void
    {
        $page = $this->graphQLRecords([self::RECORD], hasMorePages: true);
        $this->fakeGraphQL($page, $page);
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction(), WorkflowFactory::workflowOutputAction()], 'CERTAIN', $this->statusIsActive()));

        $this->mockArtisan()->shouldReceive('call')->once()->with('taurus:dispatch-workflow', [
            '--workflowId' => WorkflowFactory::ID,
            '--recordIdentifier' => 0,
            '--appendPlaceHolders' => '{"AgentName":"Jane"}',
            '--referenceId' => 'ref-1',
            '--page' => 3,
        ]);

        $this->dispatchWorkflow(appendPlaceHolders: ['AgentName' => 'Jane'], referenceId: 'ref-1', page: 2);

        $this->assertStringContainsString(' page: 3)', $this->graphQLQueriesSent()[0]);
        $this->assertSame(2, $this->workflowService->moduleSchema->getPage());
    }

    public function test_does_not_dispatch_a_next_page_on_the_last_page(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()], 'CERTAIN', $this->statusIsActive()));

        $this->mockArtisan()->shouldReceive('call')->never();

        $this->dispatchWorkflow();
    }

    public function test_a_stopped_run_still_dispatches_an_already_captured_next_page(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD], hasMorePages: true), $this->graphQLRecords([self::EMPTY_RECORD]));
        $this->givenWorkflow(
            WorkflowFactory::condition([WorkflowFactory::emailAction()]),
            WorkflowFactory::condition([WorkflowFactory::emailAction()]),
        );

        $this->mockArtisan()->shouldReceive('call')->once();

        $this->dispatchWorkflow(self::RECORD_ID);
    }

    public function test_custom_record_extraction_bypasses_jq_parsing(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([]));
        $this->workflowService->moduleSchema->customRecords = [['FirstName' => 'Custom', 'AgentEmail' => 'custom@allowed.test']];
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()]));

        $this->dispatchWorkflow(self::RECORD_ID, appendPlaceHolders: ['AgentName' => 'Jane']);

        $row = $this->executedActions()[0]->getData()[0];
        $this->assertSame(['Custom', 'Jane'], [$row['FirstName'], $row['AgentName']]);
    }

    public function test_placeholders_supplied_with_values_are_merged_into_every_row(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()]));

        $this->dispatchWorkflow(self::RECORD_ID, appendPlaceHolders: ['AgentName' => 'Jane', 'InsuredName' => '']);

        $this->assertStringContainsString('insuredName', $this->graphQLQueriesSent()[0]);
        $row = $this->executedActions()[0]->getData()[0];
        $this->assertSame(['Jane', 'Formatted: '], [$row['AgentName'], $row['InsuredName']]);
        $this->assertSame([['module' => 'policy', 'appendPlaceHolders' => ['AgentName' => 'Jane', 'InsuredName' => '']]], $this->workflowService->mappingServiceCalls);
    }

    public function test_custom_feed_file_is_passed_to_the_action(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()], 'CUSTOM_FEED', s3FilePath: '/feeds/list.csv'));

        $this->feedService('/local/feeds/list.csv')->dispatch();

        $this->assertSame('/local/feeds/list.csv', $this->executedActions()[0]->getFeedFile());
    }

    public function test_custom_feed_download_failure_is_logged_and_the_run_continues(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));
        $this->givenWorkflow(WorkflowFactory::condition([WorkflowFactory::emailAction()], 'CUSTOM_FEED', s3FilePath: '/feeds/list.csv'));

        $this->assertTrue($this->feedService(new \Exception('S3 unavailable'))->dispatch());

        Log::shouldHaveReceived('error')->with('WORKFLOW - Failed to download feed file from S3.', \Mockery::subset(['s3FilePath' => '/feeds/list.csv']));
        $this->assertSame('', $this->executedActions()[0]->getFeedFile());
    }

    // getFileOnLocal() calls S3 statically, so it is overridden rather than injected.
    private function feedService(string|\Exception $localFile): DispatchWorkflowService
    {
        return new class(WorkflowFactory::ID, self::RECORD_ID, $localFile) extends DispatchWorkflowService
        {
            public function __construct(int $workflowId, int $recordIdentifier, private string|\Exception $localFile)
            {
                parent::__construct($workflowId, $recordIdentifier);
            }

            protected function getFileOnLocal(string $s3FilePath): string
            {
                return is_string($this->localFile) ? $this->localFile : throw $this->localFile;
            }
        };
    }

    public function test_webhook_payload_fans_out_one_row_per_list_value(): void
    {
        $this->assertSame([
            ['PolicyNumber' => 'P-1', 'DriverName' => 'Ann', 'DriverAge' => 30],
            ['PolicyNumber' => 'P-1', 'DriverName' => 'Ben', 'DriverAge' => 40],
        ], $this->generatePayload(['PolicyNumber' => 'P-1', 'DriverName' => ['Ann', 'Ben'], 'DriverAge' => [30, 40]]));
    }

    public function test_webhook_payload_with_only_scalars_is_a_single_row(): void
    {
        $this->assertSame([['PolicyNumber' => 'P-1']], $this->generatePayload(['PolicyNumber' => 'P-1']));
    }

    public function test_known_bug_webhook_payload_with_lists_of_different_lengths_errors(): void
    {
        $this->expectExceptionMessage('Undefined array key 1');

        $this->generatePayload(['DriverName' => ['Ann', 'Ben'], 'DriverAge' => [30]]);
    }

    private function generatePayload(array $parsedData): array
    {
        $service = new DispatchWorkflowService(WorkflowFactory::ID);

        return (fn () => $this->generatePayloadFromParsedData($parsedData))->call($service);
    }
}
