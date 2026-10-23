<?php

namespace Taurus\Workflow\Tests\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use stdClass;
use Taurus\Workflow\Consumer\Fake\InitInstance as FakeConsumer;
use Taurus\Workflow\Data\WorkflowData;
use Taurus\Workflow\Models\Workflow;
use Taurus\Workflow\Models\WorkflowAction;
use Taurus\Workflow\Models\WorkflowCondition;
use Taurus\Workflow\Models\WorkflowLog;
use Taurus\Workflow\Repositories\Contracts\JobWorkflowRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowActionRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowConditionRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowConfigRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;
use Taurus\Workflow\Services\WorkflowService;
use Taurus\Workflow\Tests\Support\FakeWorkflowLogRepository;
use Taurus\Workflow\Tests\Support\WorkflowFactory;
use Taurus\Workflow\Tests\TestCase;

class WorkflowServiceTest extends TestCase
{
    private const MODULE_CLASS = 'App\\Models\\Policy';

    private MockInterface $workflowRepo;

    private MockInterface $conditionRepo;

    private MockInterface $actionRepo;

    private MockInterface $jobWorkflowRepo;

    private FakeWorkflowLogRepository $logRepo;

    private WorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'workflow.current_consumer' => 'fake',
            'workflowBaseData.baseData.modules' => [['systemModuleIdentifier' => 'POLICY', 'model' => self::MODULE_CLASS]],
        ]);
        FakeConsumer::reset();
        DB::spy();

        $this->service = new WorkflowService(
            $this->workflowRepo = \Mockery::mock(WorkflowRepositoryInterface::class),
            $this->conditionRepo = \Mockery::mock(WorkflowConditionRepositoryInterface::class),
            $this->actionRepo = \Mockery::mock(WorkflowActionRepositoryInterface::class),
            $this->jobWorkflowRepo = \Mockery::mock(JobWorkflowRepositoryInterface::class),
            \Mockery::mock(WorkflowConfigRepositoryInterface::class),
            $this->logRepo = new FakeWorkflowLogRepository,
        );
    }

    private function workflowData(array $conditions, int $id = 10, string $effectiveAction = 'ON_RECORD_ACTION', array $dateTimeInfo = []): WorkflowData
    {
        return WorkflowData::fromArray([
            'id' => $id,
            'detail' => ['module' => 'policy', 'name' => 'Renewal reminder', 'description' => 'Reminds agents', 'isActive' => true],
            'when' => [
                'effectiveActionToExecuteWorkflow' => $effectiveAction,
                'recordActionToExecuteWorkflow' => 'CREATE',
                'dateTimeInfoToExecuteWorkflow' => WorkflowFactory::dateTimeInfo($dateTimeInfo),
            ],
            'workFlowConditions' => $conditions,
        ]);
    }

    private function workflowRow(array $data): array
    {
        return [
            'module' => 'policy',
            'name' => 'Renewal reminder',
            'description' => 'Reminds agents',
            'effective_action_to_execute_workflow' => 'ON_RECORD_ACTION',
            'record_action_to_execute_workflow' => 'CREATE',
            'date_time_info_to_execute_workflow' => WorkflowFactory::dateTimeInfo(),
            'custom_date_time_info_to_execute_workflow' => ['cronMinutes' => '', 'cronHours' => '', 'cronDayOfMonth' => '', 'cronMonth' => '', 'cronDayOfWeek' => '', 'cronYear' => ''],
            'odyssey_action_to_execute_workflow' => '',
            ...$data,
        ];
    }

    private function workflowModel(array $conditions = [], array $attributes = []): Workflow
    {
        return (new Workflow)->forceFill([
            'id' => 10,
            'module' => 'policy',
            'name' => 'Renewal reminder',
            'description' => null,
            'is_active' => 1,
            'effective_action_to_execute_workflow' => 'ON_RECORD_ACTION',
            'record_action_to_execute_workflow' => 'CREATE',
            'date_time_info_to_execute_workflow' => WorkflowFactory::dateTimeInfo(),
            ...$attributes,
        ])->setRelation('conditions', new EloquentCollection($conditions));
    }

    private function conditionModel(int $id, array $conditions = [], array $actions = [], ?int $status = 1): WorkflowCondition
    {
        $actionModels = array_map(fn (array $action) => (new WorkflowAction)->forceFill($action), $actions);

        return (new WorkflowCondition)->forceFill(['id' => $id, 'conditions' => $conditions, 'notes' => 'note', 'status' => $status])
            ->setRelation('actions', new EloquentCollection($actionModels));
    }

    private function moduleService(): MockInterface
    {
        return FakeConsumer::$services['module'] = \Mockery::mock();
    }

    private function idCollection(int ...$ids): EloquentCollection
    {
        return new EloquentCollection(array_map(fn (int $id) => (new WorkflowCondition)->forceFill(['id' => $id]), $ids));
    }

    public function test_get_all_workflows_returns_the_repository_list(): void
    {
        $this->workflowRepo->shouldReceive('all')->once()->andReturn($all = new EloquentCollection);

        $this->assertSame($all, $this->service->getAllWorkflows());
    }

    public function test_create_workflow_stores_the_workflow_conditions_and_actions_in_a_transaction(): void
    {
        $data = $this->workflowData([[
            'applyRuleTo' => 'CERTAIN',
            'applyConditionRules' => WorkflowFactory::rule('@status', 'EQ', 'ACTIVE'),
            'notes' => 'VIP only',
            'status' => false,
            'instanceActions' => [['id' => 99, 'actionType' => 'EMAIL', 'payload' => ['id' => 'tpl-1']]],
        ]]);

        $this->workflowRepo->shouldReceive('create')->once()
            ->with($this->workflowRow(['workflow_execution_frequency' => 'ONCE', 'is_active' => true, 'field_to_observe' => '']))
            ->andReturn($workflow = (new Workflow)->forceFill(['id' => 10]));
        $this->conditionRepo->shouldReceive('create')->once()
            ->with(['workflow_id' => 10, 'conditions' => ['applyRuleTo' => 'CERTAIN', 's3FilePath' => null, 'applyConditionRules' => WorkflowFactory::rule('@status', 'EQ', 'ACTIVE')], 'notes' => 'VIP only', 'status' => 0])
            ->andReturn((new WorkflowCondition)->forceFill(['id' => 20]));
        $this->actionRepo->shouldReceive('create')->once()
            ->with(['condition_id' => 20, 'payload' => ['actionType' => 'EMAIL', 'payload' => ['id' => 'tpl-1']]]);

        $this->assertSame($workflow, $this->service->createWorkflow($data));
        DB::shouldHaveReceived('beginTransaction')->once();
        DB::shouldHaveReceived('commit')->once();
        DB::shouldNotHaveReceived('rollback');
    }

    public function test_create_workflow_marks_recurring_date_time_workflows_and_schedules_them(): void
    {
        $data = $this->workflowData([], effectiveAction: 'ON_DATE_TIME', dateTimeInfo: ['recurringFrequency' => 'WEEK']);
        $service = \Mockery::mock(WorkflowService::class, [$this->workflowRepo, $this->conditionRepo, $this->actionRepo, $this->jobWorkflowRepo, \Mockery::mock(WorkflowConfigRepositoryInterface::class), $this->logRepo])->makePartial();

        $this->workflowRepo->shouldReceive('create')->once()
            ->withArgs(fn (array $row) => $row['workflow_execution_frequency'] === 'RECURRING')
            ->andReturn((new Workflow)->forceFill(['id' => 10]));
        $this->workflowRepo->shouldReceive('getById')->with(10)->andReturn($this->workflowModel());
        $service->shouldReceive('scheduleWorkflows')->once()->withArgs(fn (array $workflows) => $workflows[0]['id'] === 10);

        $service->createWorkflow($data);
    }

    public function test_create_workflow_rolls_back_and_wraps_failures(): void
    {
        $this->workflowRepo->shouldReceive('create')->andThrow(new \Exception('duplicate name'));

        try {
            $this->service->createWorkflow($this->workflowData([]));
            $this->fail('Expected the failure to be rethrown');
        } catch (\Exception $e) {
            $this->assertSame('Failed to save workflow: duplicate name', $e->getMessage());
        }

        DB::shouldHaveReceived('rollback')->once();
        DB::shouldNotHaveReceived('commit');
    }

    public function test_get_workflow_details_by_id_maps_the_stored_workflow(): void
    {
        $condition = $this->conditionModel(20, ['applyRuleTo' => 'CUSTOM_FEED', 's3FilePath' => '/feeds/a.csv', 'applyConditionRules' => ['type' => 'group']], [
            ['id' => 30, 'payload' => ['actionType' => 'EMAIL', 'payload' => ['id' => 'tpl-1']]],
        ], status: 0);
        $this->workflowRepo->shouldReceive('getById')->with(10, true)->andReturn($this->workflowModel([$condition]));

        $details = $this->service->getWorkflowDetailsById(10, true)->toArray();

        $this->assertSame(['module' => 'policy', 'name' => 'Renewal reminder', 'description' => null, 'isActive' => true], $details['detail']);
        $this->assertSame([
            'id' => 20,
            'applyRuleTo' => 'CUSTOM_FEED',
            's3FilePath' => '/feeds/a.csv',
            'notes' => 'note',
            'status' => false,
            'applyConditionRules' => ['type' => 'group'],
            'instanceActions' => ['EMAIL' => ['id' => 30, 'actionType' => 'EMAIL', 'payload' => ['id' => 'tpl-1']]],
        ], $details['workFlowConditions'][0]);
    }

    public function test_get_workflow_details_by_id_reports_a_missing_workflow(): void
    {
        $this->workflowRepo->shouldReceive('getById')->andThrow(new \Exception('not found'));

        $this->expectExceptionMessage('No data found for the provided workflow ID: 5');

        $this->service->getWorkflowDetailsById(5);
    }

    public function test_known_bug_update_workflow_recreates_existing_actions_and_deletes_the_originals(): void
    {
        // The action id is unset before it is checked, so an existing action is never updated.
        $data = $this->workflowData([
            ['id' => 1, 'applyRuleTo' => 'ALL', 'instanceActions' => [['id' => 100, 'actionType' => 'EMAIL', 'payload' => ['id' => 'tpl-1']]]],
            ['applyRuleTo' => 'ALL', 'instanceActions' => [['actionType' => 'WEB_HOOK', 'payload' => []]]],
        ]);
        $this->workflowRepo->shouldReceive('update')->once()->with(10, \Mockery::type('array'))->andReturn($workflow = (new Workflow)->forceFill(['id' => 10]));
        $this->conditionRepo->shouldReceive('getByWorkflowId')->with(10)->andReturn($this->idCollection(1, 2));
        $this->conditionRepo->shouldReceive('update')->once()->with(1, \Mockery::type('array'))->andReturn((new WorkflowCondition)->forceFill(['id' => 1]));
        $this->conditionRepo->shouldReceive('create')->once()->andReturn((new WorkflowCondition)->forceFill(['id' => 3]));
        $this->actionRepo->shouldReceive('getByConditionId')->with(1)->andReturn($this->idCollection(100));
        $this->actionRepo->shouldReceive('getByConditionId')->with(3)->andReturn($this->idCollection());
        $this->actionRepo->shouldReceive('update')->never();
        $this->actionRepo->shouldReceive('create')->twice()->andReturn((new WorkflowAction)->forceFill(['id' => 200]), (new WorkflowAction)->forceFill(['id' => 300]));
        $this->actionRepo->shouldReceive('deleteWhereIn')->once()->with('id', [100]);
        $this->conditionRepo->shouldReceive('deleteWhereIn')->once()->withArgs(fn ($column, $ids) => $column === 'id' && array_values($ids) === [2]);

        $this->assertSame($workflow, $this->service->updateWorkflow($data));
        DB::shouldHaveReceived('commit')->once();
    }

    public function test_update_workflow_rolls_back_and_wraps_failures(): void
    {
        $this->workflowRepo->shouldReceive('update')->andThrow(new \Exception('locked'));

        $this->expectExceptionMessage('Failed to save workflow: locked');

        try {
            $this->service->updateWorkflow($this->workflowData([]));
        } finally {
            DB::shouldHaveReceived('rollback')->once();
        }
    }

    public function test_get_workflow_by_id_collects_condition_and_action_ids(): void
    {
        $this->workflowRepo->shouldReceive('getById')->with(10, false)->andReturn($this->workflowModel([
            $this->conditionModel(20, actions: [['id' => 30], ['id' => 31]]),
            $this->conditionModel(21, actions: [['id' => 32]]),
        ]));

        $this->assertSame(['id' => 10, 'conditionIds' => [20, 21], 'actionIds' => [30, 31, 32]], $this->service->getWorkflowById(10));
    }

    public function test_delete_workflow_deletes_its_conditions_actions_and_itself(): void
    {
        $this->workflowRepo->shouldReceive('getById')->andReturn($this->workflowModel([$this->conditionModel(20, actions: [['id' => 30]])]));
        $this->conditionRepo->shouldReceive('deleteWhereIn')->once()->with('id', [20]);
        $this->actionRepo->shouldReceive('deleteWhereIn')->once()->with('id', [30]);
        $this->workflowRepo->shouldReceive('delete')->once()->with(10);

        $this->assertTrue($this->service->deleteWorkflow(10));
        DB::shouldHaveReceived('commit')->once();
    }

    public function test_delete_workflow_without_conditions_only_deletes_the_workflow(): void
    {
        $this->workflowRepo->shouldReceive('getById')->andReturn($this->workflowModel());
        $this->conditionRepo->shouldReceive('deleteWhereIn')->never();
        $this->actionRepo->shouldReceive('deleteWhereIn')->never();
        $this->workflowRepo->shouldReceive('delete')->once()->with(10);

        $this->assertTrue($this->service->deleteWorkflow(10));
    }

    public function test_delete_workflow_rolls_back_and_wraps_failures(): void
    {
        $this->workflowRepo->shouldReceive('getById')->andThrow(new \Exception('not found'));

        $this->expectExceptionMessage('Failed to delete workflow: not found');

        try {
            $this->service->deleteWorkflow(10);
        } finally {
            DB::shouldHaveReceived('rollback')->once();
        }
    }

    public function test_restore_workflow_restores_its_conditions_actions_and_itself(): void
    {
        $this->workflowRepo->shouldReceive('getById')->with(10, true)->andReturn($this->workflowModel([$this->conditionModel(20, actions: [['id' => 30]])]));
        $this->conditionRepo->shouldReceive('restoreWhereIn')->once()->with([20]);
        $this->actionRepo->shouldReceive('restoreWhereIn')->once()->with([30]);
        $this->workflowRepo->shouldReceive('restore')->once()->with(10);

        $this->assertTrue($this->service->restoreWorkflow(10));
        DB::shouldHaveReceived('commit')->once();
    }

    public function test_restore_workflow_rolls_back_and_wraps_failures(): void
    {
        $this->workflowRepo->shouldReceive('getById')->andThrow(new \Exception('not found'));

        $this->expectExceptionMessage('Failed to restore workflow: not found');

        $this->service->restoreWorkflow(10);
    }

    public function test_get_matching_workflow_returns_the_matched_ids(): void
    {
        $this->workflowRepo->shouldReceive('getMatchingWorkflow')->once()->with('policy', 'EDIT', false, ['status'])->andReturn([['id' => 10], ['id' => 11]]);

        $this->assertSame([10, 11], $this->service->getMatchingWorkflow('policy', 'EDIT', ['status']));
    }

    public function test_get_matching_workflow_returns_false_without_a_match(): void
    {
        $this->workflowRepo->shouldReceive('getMatchingWorkflow')->andReturn([]);

        $this->assertFalse($this->service->getMatchingWorkflow('policy', 'EDIT'));
    }

    public function test_change_workflow_status_toggles_and_saves(): void
    {
        $workflow = \Mockery::mock(Workflow::class)->makePartial();
        $workflow->is_active = 1;
        $workflow->shouldReceive('save')->once();
        $this->workflowRepo->shouldReceive('getById')->with(10)->andReturn($workflow);

        $this->assertSame(0, $this->service->changeWorkflowStatus(10)->is_active);
    }

    public function test_known_bug_get_workflow_stats_never_returns_the_stats(): void
    {
        $this->jobWorkflowRepo->shouldReceive('getInfo')->once()->with(10)->andReturn(['status' => 'COMPLETED']);

        $this->assertNull($this->service->getWorkflowStats(10));
    }

    public function test_get_workflow_stats_returns_empty_when_the_job_is_missing(): void
    {
        $this->jobWorkflowRepo->shouldReceive('getInfo')->andThrow(new \Exception('not found'));

        $this->assertSame([], $this->service->getWorkflowStats(10));
    }

    public function test_get_query_for_effective_action_delegates_to_the_module_service(): void
    {
        $module = $this->moduleService();
        $module->shouldReceive('isCustomResolverDefinedForModule')->andReturn(false);
        $module->shouldReceive('getQueryForEffectiveAction')->once()->with('30', 'DAY', 'BEFORE', 'EXPIRY_DATE')->andReturn(['column' => 'EXPIRY_DATE']);

        $this->assertSame(['column' => 'EXPIRY_DATE'], $this->service->getQueryForEffectiveAction('policy', '30', 'DAY', 'BEFORE', 'EXPIRY_DATE'));
        $this->assertSame([['module', 'policy']], FakeConsumer::$calls);
    }

    public function test_get_query_for_effective_action_is_empty_for_custom_resolver_modules(): void
    {
        $this->moduleService()->shouldReceive('isCustomResolverDefinedForModule')->andReturn(true);

        $this->assertSame([], $this->service->getQueryForEffectiveAction('policy', '30', 'DAY', 'BEFORE', 'EXPIRY_DATE'));
    }

    public function test_consumer_lookups_fall_back_when_the_consumer_does_not_exist(): void
    {
        config(['workflow.current_consumer' => 'missing']);

        $this->assertSame([], $this->service->getQueryForEffectiveAction('policy', '30', 'DAY', 'BEFORE', 'EXPIRY_DATE'));
        $this->assertSame('', $this->service->getServicePostFixForModule('policy'));
        $this->assertSame([], $this->service->getExtendedTemplateInfoForModule('policy'));
        $this->assertInstanceOf(stdClass::class, $this->service->getGraphQLQueryMappingService('policy'));
        Log::shouldHaveReceived('error')->with("Consumer service class 'Taurus\\Workflow\\Consumer\\Missing\\InitInstance' does not exist.");
    }

    public function test_known_bug_get_query_for_record_identifier_errors_when_the_module_service_is_missing(): void
    {
        // stdClass fallback has no getQueryForRecordIdentifier(); the \Error escapes the dispatch engines' catch (\Exception).
        FakeConsumer::$services['module'] = new \Exception('no module');

        $this->expectException(\Error::class);

        $this->service->getQueryForRecordIdentifier('policy', 42);
    }

    public function test_get_query_for_record_identifier_delegates_to_the_module_service(): void
    {
        $this->moduleService()->shouldReceive('getQueryForRecordIdentifier')->once()->with('policy', 42)->andReturn(['column' => 'POLICY_ID']);

        $this->assertSame(['column' => 'POLICY_ID'], $this->service->getQueryForRecordIdentifier('policy', 42));
    }

    public function test_get_service_post_fix_delegates_to_the_module_service(): void
    {
        $this->moduleService()->shouldReceive('getServicePostFix')->with('policy')->andReturn('-policy');

        $this->assertSame('-policy', $this->service->getServicePostFixForModule('policy'));
    }

    public function test_get_extended_template_info_delegates_and_swallows_module_failures(): void
    {
        $module = $this->moduleService();
        $module->shouldReceive('getExtendedTemplateInfo')->with(['id' => 'tpl-1'])->andReturn(['logo' => 'a.png']);

        $this->assertSame(['logo' => 'a.png'], $this->service->getExtendedTemplateInfoForModule('policy', ['id' => 'tpl-1']));

        $module->shouldReceive('getExtendedTemplateInfo')->with([])->andThrow(new \Exception('template api down'));

        $this->assertSame([], $this->service->getExtendedTemplateInfoForModule('policy'));
    }

    public function test_get_graphql_query_mapping_service_passes_the_appended_placeholders(): void
    {
        FakeConsumer::$services['graphQL'] = $schema = new stdClass;

        $this->assertSame($schema, $this->service->getGraphQLQueryMappingService('policy', ['AgentName' => 'Jane']));
        $this->assertSame([['graphQL', 'policy', ['AgentName' => 'Jane']]], FakeConsumer::$calls);
    }

    public function test_consumer_services_are_resolved_from_the_consumer(): void
    {
        FakeConsumer::$services = ['postAction' => $post = new stdClass, 'createRecord' => $create = new stdClass, 'parentClass' => $parent = new stdClass];

        $this->assertSame([$post, $create, $parent], [
            $this->service->getPostActionService(),
            $this->service->getCreateRecordService(),
            $this->service->getParentClassService(),
        ]);
    }

    public function test_consumer_service_failures_fall_back_to_an_empty_object(): void
    {
        $failure = new \Exception('consumer down');
        FakeConsumer::$services = ['graphQL' => $failure, 'postAction' => $failure, 'createRecord' => $failure, 'parentClass' => $failure];

        foreach (['getGraphQLQueryMappingService', 'getPostActionService', 'getCreateRecordService', 'getParentClassService'] as $method) {
            $this->assertEquals(new stdClass, $method === 'getGraphQLQueryMappingService' ? $this->service->$method('policy') : $this->service->$method());
        }
    }

    public function test_get_odyssey_actions_groups_accounting_actions_by_module(): void
    {
        $actions = \Mockery::mock();
        $actions->shouldReceive('get')->andReturn(collect([
            (object) ['workflow_module' => 'policy', 's_PATranTypeCode' => 'NB', 's_TranTypeScreenName' => 'New Business', 'n_PAActionCode_PK' => 1],
            (object) ['workflow_module' => 'policy', 's_PATranTypeCode' => 'RN', 's_TranTypeScreenName' => 'Renewal', 'n_PAActionCode_PK' => 2],
            (object) ['workflow_module' => 'claim', 's_PATranTypeCode' => 'CL', 's_TranTypeScreenName' => 'Claim', 'n_PAActionCode_PK' => 3],
        ]));
        DB::shouldReceive('table')->with('tb_paaccoutingactions')->andReturn($actions);

        $this->assertSame([
            'policy' => [
                ['value' => 'NB', 'label' => 'New Business', 'n_PAActionCode_PK' => 1],
                ['value' => 'RN', 'label' => 'Renewal', 'n_PAActionCode_PK' => 2],
            ],
            'claim' => [['value' => 'CL', 'label' => 'Claim', 'n_PAActionCode_PK' => 3]],
        ], $this->service->getOdysseyActions());
    }

    public function test_get_odyssey_actions_returns_empty_on_failure(): void
    {
        DB::shouldReceive('table')->andThrow(new \Exception('table missing'));

        $this->assertSame([], $this->service->getOdysseyActions());
    }

    public function test_get_workflow_log_queries_completed_logs_for_the_module_model(): void
    {
        $this->logRepo->completedLogs = ['data' => collect(['log']), 'total' => 1];

        $this->assertSame($this->logRepo->completedLogs, $this->service->getWorkflowlog('policy', 10, 20, 7));
        $this->assertSame([['getCompleted', self::MODULE_CLASS, 10, 20, 7]], $this->logRepo->queries);
    }

    public function test_log_queries_are_empty_for_an_unknown_module(): void
    {
        $this->assertEquals(['data' => collect(), 'total' => 0], $this->service->getWorkflowlog('unknown'));
        $this->assertSame(['labels' => [], 'data' => []], $this->service->getWorkflowLogChart('unknown'));
        $this->assertSame([], $this->service->getWorkflowLogFilterDD('unknown'));
        $this->assertEquals(collect(), $this->service->moduleWiseWorkflow('unknown'));
        $this->assertSame([], $this->logRepo->queries);
    }

    public function test_get_workflow_log_chart_fills_the_last_seven_days(): void
    {
        $this->travelTo('2026-03-10 15:00:00');
        $this->logRepo->dailyCounts = collect([
            '2026-03-04' => (object) ['log_count' => 2],
            '2026-03-10' => (object) ['log_count' => '5'],
        ]);

        $this->assertSame([
            'labels' => ['Mar 4', 'Mar 5', 'Mar 6', 'Mar 7', 'Mar 8', 'Mar 9', 'Mar 10'],
            'data' => [2, 0, 0, 0, 0, 0, 5],
        ], $this->service->getWorkflowLogChart('policy', 7));
        $this->assertSame([['getDailyCompletedCounts', self::MODULE_CLASS, '2026-03-04 00:00:00', '2026-03-10 23:59:59', 7]], $this->logRepo->queries);
    }

    public function test_get_workflow_log_filter_lists_each_workflow_once_naming_manual_runs(): void
    {
        $this->logRepo->completedWorkflows = collect([
            (object) ['workflow_id' => 10, 'workflow' => (object) ['name' => 'Renewal reminder']],
            (object) ['workflow_id' => null, 'workflow' => null],
            (object) ['workflow_id' => 10, 'workflow' => (object) ['name' => 'Renewal reminder']],
        ]);

        $this->assertSame([
            ['id' => 10, 'name' => 'Renewal reminder'],
            ['id' => null, 'name' => 'Manual Workflow'],
        ], $this->service->getWorkflowLogFilterDD('policy'));
    }

    public function test_module_wise_workflow_filters_by_module_and_loads_conditions(): void
    {
        $all = \Mockery::mock(EloquentCollection::class);
        $filtered = \Mockery::mock(EloquentCollection::class);
        $this->workflowRepo->shouldReceive('all')->with(true)->andReturn($all);
        $all->shouldReceive('where')->with('module', self::MODULE_CLASS)->andReturn($filtered);
        $filtered->shouldReceive('load')->once()->with(['conditions.actions'])->andReturnSelf();
        $filtered->shouldReceive('values')->andReturn($result = new EloquentCollection);

        $this->assertSame($result, $this->service->moduleWiseWorkflow('policy'));
    }

    public function test_add_workflow_log_appends_the_error_to_the_jobs_log(): void
    {
        $log = \Mockery::mock(WorkflowLog::class)->makePartial();
        $log->error = json_encode([['errorType' => 'GRAPHQL_ERROR', 'exception' => 'timeout']]);
        $log->shouldReceive('save')->once();
        $this->logRepo->logForJob = $log;

        $this->service->addWorkflowLog(10, 1, 'MISSING_MANDATE_DATA', ['missing' => ['PolicyNumber']]);

        $this->assertSame([
            ['errorType' => 'GRAPHQL_ERROR', 'exception' => 'timeout'],
            ['errorType' => 'MISSING_MANDATE_DATA', 'exception' => ['missing' => ['PolicyNumber']]],
        ], json_decode($log->error, true));
        $this->assertSame([['findForJob', 10, 1]], $this->logRepo->queries);
    }

    public function test_known_bug_add_workflow_log_throws_an_uncaught_error_when_no_log_exists(): void
    {
        // \Error escapes the dispatch engines' catch (\Exception) blocks.
        $this->expectException(\Error::class);

        $this->service->addWorkflowLog(10, 1, 'GRAPHQL_ERROR');
    }
}
