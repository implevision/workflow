<?php

namespace Taurus\Workflow\Tests\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Taurus\Workflow\Services\DispatchManualWorkflowService;
use Taurus\Workflow\Tests\Support\FakeModuleSchema;
use Taurus\Workflow\Tests\Support\InteractsWithDispatch;
use Taurus\Workflow\Tests\Support\WorkflowFactory;
use Taurus\Workflow\Tests\TestCase;

class DispatchManualWorkflowServiceTest extends TestCase
{
    use InteractsWithDispatch;

    private const RECORD = ['policyNumber' => 'P-100', 'firstName' => 'Ann', 'agentEmail' => 'agent@allowed.test'];

    private const NOTHING_RAN = ['success' => false, 'jobWorkflowId' => null, 'results' => []];

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDispatchCollaborators();
    }

    private function dispatchManual(array $selectedActions, array $actionsConfig): array
    {
        return (new DispatchManualWorkflowService(WorkflowFactory::MODULE, 42, $selectedActions, $actionsConfig, 7))->dispatch();
    }

    private function config(array $email = [], array $workflowOutput = []): array
    {
        return [
            'EMAIL' => ['emailRecipient' => 'AgentEmail', 'result' => 'sent', ...$email],
            'WORKFLOW_OUTPUT' => ['result' => ['pdf' => 'letter.pdf'], ...$workflowOutput],
        ];
    }

    public function test_returns_failure_when_no_actions_are_selected(): void
    {
        $this->assertSame(self::NOTHING_RAN, $this->dispatchManual([], []));
        $this->assertSame([], $this->jobWorkflowRepo->created);
    }

    public function test_returns_failure_when_the_job_cannot_be_created(): void
    {
        $this->jobWorkflowRepo->failure = new \Exception('database unavailable');

        $this->assertSame(self::NOTHING_RAN, $this->dispatchManual(['EMAIL'], $this->config()));
        $this->assertSame([], $this->workflowLogRepo->created);
    }

    public function test_runs_each_selected_action_for_the_record(): void
    {
        setRunningWorkflowId(99);
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]), $this->graphQLRecords([self::RECORD]));

        $result = $this->dispatchManual(['EMAIL', 'WORKFLOW_OUTPUT'], $this->config());

        $this->assertSame(['success' => true, 'jobWorkflowId' => 1, 'results' => ['EMAIL' => 'sent', 'WORKFLOW_OUTPUT' => ['pdf' => 'letter.pdf']]], $result);
        $this->assertNull($this->jobWorkflowRepo->created[0]['workflow_id']);
        $this->assertSame(['EMAIL', 'WORKFLOW_OUTPUT'], array_column($this->workflowLogRepo->created, 'action_type'));
        $this->assertSame([$this->config()['EMAIL'], 0, 7], [
            $this->workflowLogRepo->created[0]['action_config_payload'],
            $this->workflowLogRepo->created[0]['workflow_id'],
            $this->workflowLogRepo->created[0]['user_id'],
        ]);
        $this->assertSame([[0, 1]], $this->workflowLogRepo->completed);
        $this->assertStringContainsString('where: { column: POLICY_ID, operator: EQ, value: "42" } page: 1', $this->graphQLQueriesSent()[0]);
        $this->assertSame(['Ann', ['agent@allowed.test']], [$this->executedActions()[0]->getData()[0]['FirstName'], $this->executedActions()[0]->getData()[0]['email']]);
        $this->assertSame(0, getRunningWorkflowId());
        $this->assertSame('policy', getModuleForCurrentWorkflow());
    }

    public function test_known_bug_actions_returning_null_are_left_out_of_results(): void
    {
        // EmailAction::execute() returns nothing, so a sent email never appears in results.
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));

        $result = $this->dispatchManual(['EMAIL'], $this->config(['result' => null]));

        $this->assertCount(1, $this->executedActions());
        $this->assertSame([], $result['results']);
    }

    public function test_actions_outside_the_manual_allowlist_are_rejected(): void
    {
        $result = $this->dispatchManual(['WEB_HOOK', 'CREATE_RECORD'], ['WEB_HOOK' => ['url' => 'x'], 'CREATE_RECORD' => ['module' => 'task']]);

        $this->assertSame([], $result['results']);
        $this->assertSame(['WEB_HOOK', 'CREATE_RECORD'], array_column($this->workflowLogRepo->created, 'action_type'));
        $this->assertSame([], $this->actions);
        Log::shouldHaveReceived('error')->with('MANUAL WORKFLOW - Unsupported action type: WEB_HOOK');
    }

    public function test_a_selected_action_without_config_is_logged_and_skipped(): void
    {
        $this->assertSame([], $this->dispatchManual(['EMAIL'], [])['results']);
        $this->assertSame(['EMPTY_ACTION_CONFIG'], $this->loggedErrorTypes());
    }

    public function test_a_failed_action_does_not_stop_the_next_one(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));

        $result = $this->dispatchManual(['EMAIL', 'WORKFLOW_OUTPUT'], $this->config(['failOnHandle' => true]));

        $this->assertSame(['WORKFLOW_OUTPUT' => ['pdf' => 'letter.pdf']], $result['results']);
        $this->assertSame(['ERROR_INITIATING_ACTION'], $this->loggedErrorTypes());
    }

    public function test_an_execute_failure_is_reported(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD]));

        $this->assertSame([], $this->dispatchManual(['EMAIL'], $this->config(['failOnExecute' => true]))['results']);
        $this->assertSame(['ERROR_EXECUTING_ACTION'], $this->loggedErrorTypes());
    }

    public function test_reports_success_even_when_every_action_fails(): void
    {
        $this->fakeGraphQL(Http::response('upstream down', 502));

        $result = $this->dispatchManual(['EMAIL'], $this->config());

        $this->assertSame([true, []], [$result['success'], $result['results']]);
        $this->assertSame(['GRAPHQL_ERROR'], $this->loggedErrorTypes());
    }

    public function test_record_query_failure_skips_the_action(): void
    {
        $this->workflowService->recordIdentifierQuery = new \Exception('module offline');

        $this->assertSame([], $this->dispatchManual(['EMAIL'], $this->config())['results']);
        $this->assertSame(['GRAPHQL_ERROR'], $this->loggedErrorTypes());
        $this->assertSame([], $this->graphQLQueriesSent());
    }

    public function test_a_paginated_response_uses_only_the_first_record(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([self::RECORD, ['firstName' => 'Ben', 'policyNumber' => 'P-200', 'agentEmail' => 'ben@allowed.test']]));

        $this->dispatchManual(['EMAIL'], $this->config());

        $this->assertSame(['Ann'], array_column($this->executedActions()[0]->getData(), 'FirstName'));
    }

    public function test_a_non_paginated_module_reads_the_record_from_the_query_root(): void
    {
        $this->workflowService->moduleSchema->paginated = false;
        $this->fakeGraphQL([FakeModuleSchema::QUERY_NAME => self::RECORD]);

        $this->dispatchManual(['EMAIL'], $this->config());

        $this->assertStringNotContainsString('paginatorInfo', $this->graphQLQueriesSent()[0]);
        $this->assertSame('Ann', $this->executedActions()[0]->getData()[0]['FirstName']);
    }
}
