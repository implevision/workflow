<?php

namespace Taurus\Workflow\Tests\Services;

use Illuminate\Support\Facades\Http;
use Taurus\Workflow\Tests\Support\FakeAction;
use Taurus\Workflow\Tests\Support\FakeModuleSchema;
use Taurus\Workflow\Tests\Support\FakeSchemaBuilder;
use Taurus\Workflow\Tests\Support\InteractsWithDispatch;
use Taurus\Workflow\Tests\Support\TestableDispatchService;
use Taurus\Workflow\Tests\TestCase;

class AbstractDispatchServiceTest extends TestCase
{
    use InteractsWithDispatch;

    private TestableDispatchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDispatchCollaborators();
        $this->service = new TestableDispatchService;
    }

    public function test_create_job_workflow_entry_stores_a_created_job_and_registers_it_globally(): void
    {
        $id = $this->service->call('createJobWorkflowEntry', 7, 'ref-123');

        $this->assertSame(1, $id);
        $this->assertSame(1, getRunningJobWorkflowId());
        $this->assertSame(
            ['workflow_id' => 7, 'status' => 'CREATED', 'total_no_of_records_to_execute' => 0, 'total_no_of_records_executed' => 0, 'response' => [], 'reference_id' => 'ref-123'],
            $this->jobWorkflowRepo->created[0]
        );
    }

    public function test_create_job_workflow_entry_returns_zero_when_the_repository_fails(): void
    {
        $this->jobWorkflowRepo->failure = new \Exception('validation failed');

        $this->assertSame(0, $this->service->call('createJobWorkflowEntry', 7));
    }

    public function test_create_in_progress_log_records_the_action_for_the_run(): void
    {
        $this->service->call('createJobWorkflowEntry', null);

        $this->service->call('createInProgressLog', 'EMAIL', ['id' => 'tpl-1']);

        $this->assertSame([
            'job_workflow_id' => 1,
            'workflow_id' => 0,
            'record_identifier' => 0,
            'module' => 'policy',
            'status' => 'IN_PROGRESS',
            'action_type' => 'EMAIL',
            'user_id' => null,
            'action_config_payload' => ['id' => 'tpl-1'],
        ], $this->workflowLogRepo->created[0]);
    }

    public function test_partition_placeholders_splits_valued_and_empty_placeholders(): void
    {
        [$withValues, $toExtract] = $this->service->call('partitionPlaceholders', [
            'AgentName' => 'Jane',
            'AgentEmail' => '',
            'PolicyNumber' => null,
            'Zero' => 0,
        ]);

        $this->assertSame(['AgentName' => 'Jane'], $withValues);
        $this->assertSame(['AgentEmail', 'PolicyNumber', 'Zero'], $toExtract);
    }

    public function test_instantiate_action_returns_null_for_an_unknown_action_type(): void
    {
        $this->assertNull($this->service->call('instantiateAction', 'SMS', []));
    }

    public function test_instantiate_action_returns_null_for_an_action_type_the_engine_does_not_support(): void
    {
        $this->assertNull($this->service->call('instantiateAction', 'WEB_HOOK', [], ['EMAIL']));
    }

    public function test_instantiate_action_handles_the_action_with_module_template_info(): void
    {
        $this->workflowService->extendedTemplateInfo = ['html' => '<div>Template</div>'];

        $action = $this->service->call('instantiateAction', 'EMAIL', ['id' => 'tpl-1']);

        $this->assertInstanceOf(FakeAction::class, $action);
        $this->assertSame(['EMAIL', ['id' => 'tpl-1']], [$action->getCurrentAction(), $action->getPayload()]);
        $this->assertSame(['html' => '<div>Template</div>'], $action->getExtendedTemplateInfo());
    }

    public function test_instantiate_action_skips_template_info_for_webhooks(): void
    {
        $this->workflowService->extendedTemplateInfo = ['html' => '<div>Template</div>'];

        $this->assertSame([], $this->service->call('instantiateAction', 'WEB_HOOK', [])->getExtendedTemplateInfo());
    }

    public function test_instantiate_action_wraps_handle_failures_in_a_runtime_exception(): void
    {
        try {
            $this->service->call('instantiateAction', 'EMAIL', ['failOnHandle' => true]);
            $this->fail('Expected a RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('handle failed', $e->getMessage());
        }

        $this->assertSame(['ERROR_INITIATING_ACTION'], $this->loggedErrorTypes());
    }

    public function test_email_action_requires_and_mandates_the_recipient_placeholder(): void
    {
        $payload = ['emailRecipient' => 'AgentEmail', 'mandatoryPlaceholders' => ['PolicyNumber']];

        [$required, $mandated] = $this->service->call('resolveActionDataRequirements', new FakeAction('EMAIL', $payload), 'EMAIL', $payload, ['Extra']);

        $this->assertSame(['FirstName', 'PolicyNumber', 'Extra', 'AgentEmail'], $required);
        $this->assertSame(['PolicyNumber', 'AgentEmail'], $mandated);
    }

    public function test_placeholders_that_were_not_passed_are_not_required(): void
    {
        $payload = ['emailRecipient' => 'AgentEmail'];

        [$required, $mandated] = $this->service->call('resolveActionDataRequirements', new FakeAction('EMAIL', $payload), 'EMAIL', $payload);

        $this->assertNotContains('Extra', $required);
        $this->assertNotContains('Extra', $mandated);
    }

    public function test_email_action_with_custom_recipients_does_not_require_a_recipient_placeholder(): void
    {
        $payload = ['emailRecipient' => 'CUSTOM'];

        [$required, $mandated] = $this->service->call('resolveActionDataRequirements', new FakeAction('EMAIL', $payload), 'EMAIL', $payload);

        $this->assertSame(['FirstName', 'PolicyNumber'], $required);
        $this->assertSame([], $mandated);
    }

    public function test_create_record_action_also_requires_the_schema_create_record_placeholders(): void
    {
        $this->workflowService->moduleSchema->requiredPlaceholdersForCreateRecord = ['PolicyNumber', 'PolicyId'];
        $payload = ['requiredData' => ['PolicyNumber']];

        [$required] = $this->service->call('resolveActionDataRequirements', new FakeAction('CREATE_RECORD', $payload), 'CREATE_RECORD', $payload);

        $this->assertSame(['PolicyNumber', 'PolicyId'], $required);
    }

    public function test_known_bug_email_action_without_email_recipient_throws_instead_of_failing_gracefully(): void
    {
        $this->expectExceptionMessage('Undefined array key "emailRecipient"');

        $this->service->call('resolveActionDataRequirements', new FakeAction('EMAIL', []), 'EMAIL', []);
    }

    public function test_parse_graphql_response_resolves_each_placeholder_by_its_mapping(): void
    {
        $response = [FakeModuleSchema::QUERY_NAME => ['firstName' => 'Ann', 'insuredName' => 'Bob', 'tags' => ['gold', 'auto']]];
        $builder = new FakeSchemaBuilder($this->workflowService->moduleSchema->getFieldMapping());

        $parsed = $this->service->call('parseGraphQLResponse', $response, ['FirstName', 'InsuredName', 'Tags', 'Today', 'Broken', 'PolicyNumber'], $builder);

        $this->assertSame([
            'FirstName' => 'Ann',
            'InsuredName' => 'Formatted: Bob',
            'Tags' => ['gold', 'auto'],
            'Today' => '2026-01-01',
            'Broken' => '',
            'PolicyNumber' => null,
        ], $parsed);
    }

    public function test_parse_graphql_response_reports_placeholders_without_a_field_mapping(): void
    {
        $parsed = $this->service->call('parseGraphQLResponse', [], ['Unmapped'], new FakeSchemaBuilder([]));

        $this->assertSame(['Unmapped' => ''], $parsed);
        $this->assertSame(['FIELD_MAPPING_ISSUE'], $this->loggedErrorTypes());
    }

    public function test_validate_and_filter_data_drops_rows_missing_mandatory_data(): void
    {
        $rows = [['PolicyNumber' => 'P-1'], ['PolicyNumber' => ''], ['PolicyNumber' => 'P-3']];

        $result = $this->service->call('validateAndFilterData', $rows, ['PolicyNumber'], [], 'WEB_HOOK');

        $this->assertSame([
            0 => ['PolicyNumber' => 'P-1', 'hasPriorDataForWorkflow' => true],
            2 => ['PolicyNumber' => 'P-3', 'hasPriorDataForWorkflow' => true],
        ], $result);
        $this->assertSame(['MISSING_MANDATE_DATA'], $this->loggedErrorTypes());
    }

    public function test_validate_and_filter_data_returns_false_when_no_row_is_valid(): void
    {
        $this->assertFalse($this->service->call('validateAndFilterData', [['PolicyNumber' => null]], ['PolicyNumber'], [], 'WEB_HOOK'));
    }

    public function test_validate_and_filter_data_returns_false_for_no_rows(): void
    {
        $this->assertFalse($this->service->call('validateAndFilterData', [], ['PolicyNumber'], [], 'EMAIL'));
    }

    public function test_validate_and_filter_data_resolves_the_recipient_for_email_rows(): void
    {
        $rows = [['AgentEmail' => 'agent@allowed.test'], ['AgentEmail' => 'someone@blocked.com']];

        $result = $this->service->call('validateAndFilterData', $rows, [], ['emailRecipient' => 'AgentEmail'], 'EMAIL');

        $this->assertSame([
            0 => ['AgentEmail' => 'agent@allowed.test', 'hasPriorDataForWorkflow' => true, 'email' => ['agent@allowed.test']],
        ], $result);
        $this->assertSame(['UNAUTHORIZED_EMAIL_ADDRESS'], $this->loggedErrorTypes());
    }

    public function test_custom_recipients_are_taken_from_the_payload(): void
    {
        $payload = ['emailRecipient' => 'CUSTOM', 'customEmailRecipients' => 'a@allowed.test,b@allowed.test'];

        $this->assertSame(['a@allowed.test', 'b@allowed.test'], $this->service->call('resolveEmailForAction', $payload, [[]], 0, []));
    }

    public function test_a_missing_recipient_address_is_reported(): void
    {
        $this->service->call('resolveEmailForAction', ['emailRecipient' => 'AgentEmail'], [[]], 0, []);

        $this->assertSame(['MISSING_EMAIL_ADDRESS', 'UNAUTHORIZED_EMAIL_ADDRESS'], $this->loggedErrorTypes());
    }

    public function test_in_production_recipients_are_not_filtered(): void
    {
        config(['app.env' => 'production']);
        $rows = [['AgentEmail' => 'x@anywhere.com,y@else.com']];

        $this->assertSame(['x@anywhere.com', 'y@else.com'], $this->service->call('resolveEmailForAction', ['emailRecipient' => 'AgentEmail'], $rows, 0, []));
    }

    public function test_known_bug_place_holder_to_extract_recipient_branch_is_unreachable(): void
    {
        // partitionPlaceholders() returns a list, so $placeHolderToExtract['emailRecipient'] is never set.
        $rows = [['AgentEmail' => 'agent@allowed.test', 'EmailRecipient' => 'other@allowed.test']];

        $this->assertSame(['agent@allowed.test'], $this->service->call('resolveEmailForAction', ['emailRecipient' => 'AgentEmail'], $rows, 0, ['emailRecipient']));
    }

    public function test_non_production_allows_exact_addresses_on_the_allowlist(): void
    {
        $this->assertSame(['allowed@example.com'], $this->service->call('filterEmailForNonProduction', 'allowed@example.com', []));
    }

    public function test_non_production_allows_addresses_ending_with_an_allowed_suffix(): void
    {
        $this->assertSame(
            ['c@thinktaurus.com', 'b@allowed.test'],
            $this->service->call('filterEmailForNonProduction', 'a@blocked.com,b@allowed.test,c@thinktaurus.com', [])
        );
    }

    public function test_non_production_blocks_addresses_not_on_any_allowlist(): void
    {
        $this->assertFalse($this->service->call('filterEmailForNonProduction', 'a@blocked.com', []));
        $this->assertSame(['UNAUTHORIZED_EMAIL_ADDRESS'], $this->loggedErrorTypes());
    }

    public function test_send_all_override_replaces_placeholder_recipients(): void
    {
        config(['workflow.send_all_workflow_email_to' => 'qa@allowed.test']);

        $this->assertSame(['qa@allowed.test'], $this->service->call('filterEmailForNonProduction', 'a@blocked.com', ['emailRecipient' => 'AgentEmail']));
    }

    public function test_send_all_override_does_not_apply_to_custom_recipients(): void
    {
        config(['workflow.send_all_workflow_email_to' => 'qa@allowed.test']);

        $this->assertSame(['custom@allowed.test'], $this->service->call('filterEmailForNonProduction', 'custom@allowed.test', ['emailRecipient' => 'custom']));
    }

    public function test_known_bug_unset_ends_with_env_allows_every_address(): void
    {
        // explode(',', '') yields [''], and every address ends with ''.
        config(['workflow.allowed_receiver.email' => [''], 'workflow.allowed_receiver.ends_with' => ['']]);

        $this->assertSame(['anyone@real-customer.com'], $this->service->call('filterEmailForNonProduction', 'anyone@real-customer.com', []));
    }

    public function test_build_and_execute_graphql_query_returns_null_and_logs_when_the_request_fails(): void
    {
        $this->fakeGraphQL(Http::response('boom', 500));

        $this->assertNull($this->service->call('buildAndExecuteGraphQLQuery', ['FirstName'], ['column' => 'POLICY_ID', 'operator' => 'EQ', 'value' => 1], false));
        $this->assertSame(['GRAPHQL_ERROR'], $this->loggedErrorTypes());
    }

    public function test_build_and_execute_graphql_query_returns_null_and_logs_when_the_query_cannot_be_built(): void
    {
        $this->assertNull($this->service->call('buildAndExecuteGraphQLQuery', ['FirstName'], [], false));
        $this->assertSame(['GRAPHQL_ERROR'], $this->loggedErrorTypes());
        $this->assertSame([], $this->graphQLQueriesSent());
    }

    public function test_build_and_execute_graphql_query_sends_the_built_query(): void
    {
        $this->fakeGraphQL($this->graphQLRecords([['firstName' => 'Ann']]));

        $result = $this->service->call('buildAndExecuteGraphQLQuery', ['FirstName'], ['column' => 'POLICY_ID', 'operator' => 'EQ', 'value' => 7], false);

        $this->assertSame($this->graphQLRecords([['firstName' => 'Ann']]), $result['response']);
        $this->assertSame(
            "query {\n  policiesQuery(where: { column: POLICY_ID, operator: EQ, value: \"7\" } page: 1){\n".
            "    data {\n      firstName\n    }\n    paginatorInfo {\n      hasMorePages\n    }\n  }\n}",
            $this->graphQLQueriesSent()[0]
        );
    }
}
