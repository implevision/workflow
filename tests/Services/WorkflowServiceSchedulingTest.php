<?php

namespace Taurus\Workflow\Tests\Services;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\Scheduler\SchedulerClient;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Taurus\Workflow\Consumer\Fake\InitInstance as FakeConsumer;
use Taurus\Workflow\Models\WorkflowConfig;
use Taurus\Workflow\Repositories\Contracts\JobWorkflowRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowActionRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowConditionRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowConfigRepositoryInterface;
use Taurus\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;
use Taurus\Workflow\Services\WorkflowService;
use Taurus\Workflow\Tests\Support\FakeWorkflowLogRepository;
use Taurus\Workflow\Tests\Support\WorkflowFactory;
use Taurus\Workflow\Tests\TestCase;

class WorkflowServiceSchedulingTest extends TestCase
{
    private const GROUP = 'workflow-auto-generated-NO_TENANT_FOUND';

    private const GROUP_ARN = 'arn:aws:scheduler:us-east-1:1:schedule-group/workflow';

    private MockInterface $workflowRepo;

    private MockInterface $configRepo;

    private MockHandler $aws;

    private array $awsCommands = [];

    private WorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'workflow.current_consumer' => 'fake',
            'workflow.timezone' => 'America/New_York',
            'workflow.aws_lambda_function_arn_to_invoke_workflow' => 'arn:aws:lambda:us-east-1:1:function:workflow',
            'workflow.aws_iam_role_arn_to_invoke_lambda_from_event_bridge' => 'arn:aws:iam::1:role/scheduler',
            'workflow.task_definition_prefix' => 'workflow-task',
        ]);
        FakeConsumer::reset();
        FakeConsumer::$services['module'] = \Mockery::mock(['getServicePostFix' => '-policy']);

        $this->aws = new MockHandler;
        $this->app->bind(SchedulerClient::class, fn ($app, $params) => new SchedulerClient([...$params['args'], 'credentials' => false, 'handler' => $this->aws]));

        $this->service = new WorkflowService(
            $this->workflowRepo = \Mockery::mock(WorkflowRepositoryInterface::class),
            \Mockery::mock(WorkflowConditionRepositoryInterface::class),
            \Mockery::mock(WorkflowActionRepositoryInterface::class),
            \Mockery::mock(JobWorkflowRepositoryInterface::class),
            $this->configRepo = \Mockery::mock(WorkflowConfigRepositoryInterface::class),
            new FakeWorkflowLogRepository,
        );
    }

    // Each argument answers one AWS call in order: an array is the result, a string fails the call with that message.
    private function awsReturns(array|string ...$responses): void
    {
        foreach ($responses as $response) {
            $this->aws->append(function (CommandInterface $command) use ($response) {
                $this->awsCommands[] = [$command->getName(), $command->toArray()];

                return is_string($response) ? new AwsException($response, $command) : new Result($response);
            });
        }
    }

    private function awsCommandNames(): array
    {
        return array_column($this->awsCommands, 0);
    }

    private function givenScheduleGroup(): void
    {
        $this->configRepo->shouldReceive('getByKey')->with('schedule_group_arn')->andReturn((new WorkflowConfig)->forceFill(['config_value' => self::GROUP_ARN]));
    }

    private function workflow(string $effectiveAction = 'ON_DATE_TIME', array $dateTimeInfo = [], array $attributes = []): array
    {
        return [
            'id' => 10,
            'module' => 'policy',
            'effective_action_to_execute_workflow' => $effectiveAction,
            'date_time_info_to_execute_workflow' => WorkflowFactory::dateTimeInfo($dateTimeInfo),
            'custom_date_time_info_to_execute_workflow' => [],
            'aws_event_bridge_arn' => null,
            ...$attributes,
        ];
    }

    private function scheduledExpression(): string
    {
        return $this->awsCommands[0][1]['ScheduleExpression'];
    }

    public function test_workflows_that_are_not_date_based_are_not_scheduled(): void
    {
        $this->configRepo->shouldReceive('getByKey')->never();

        $this->service->scheduleWorkflows([$this->workflow('ON_RECORD_ACTION')]);

        $this->assertSame([], $this->awsCommands);
    }

    public function test_a_dated_workflow_is_scheduled_once_at_its_utc_time_and_the_arn_is_stored(): void
    {
        $this->givenScheduleGroup();
        $this->awsReturns(['ScheduleArn' => 'arn:schedule/10']);
        $this->workflowRepo->shouldReceive('update')->once()->with(10, ['aws_event_bridge_arn' => 'arn:schedule/10']);

        $this->service->scheduleWorkflows([$this->workflow(dateTimeInfo: ['executionEffectiveDate' => '01/15/2026', 'executionEffectiveTime' => '10:30'])]);

        [[$name, $params]] = $this->awsCommands;
        $this->assertSame('CreateSchedule', $name);
        $this->assertSame([
            'Name' => 'workflow-id-10-NO_TENANT_FOUND',
            'ScheduleExpression' => 'at(2026-01-15T15:30:00)',
            'GroupName' => self::GROUP,
            'FlexibleTimeWindow' => ['Mode' => 'OFF'],
            'Target' => [
                'Arn' => 'arn:aws:lambda:us-east-1:1:function:workflow',
                'RoleArn' => 'arn:aws:iam::1:role/scheduler',
                'Input' => json_encode([
                    'task_definition' => 'workflow-task-policy',
                    'command' => ['php', 'artisan', 'taurus:dispatch-workflow', '--workflowId=10', '--recordIdentifier=0'],
                ]),
            ],
        ], array_intersect_key($params, array_flip(['Name', 'ScheduleExpression', 'GroupName', 'FlexibleTimeWindow', 'Target'])));
    }

    public static function recurringFrequencies(): array
    {
        return [
            'weekly' => ['WEEK', 'cron(0 0 ? * MON *)'],
            'monthly' => ['MONTH', 'cron(0 0 1 * ? *)'],
            'yearly' => ['YEAR', 'cron(0 0 1 1 ? *)'],
            'daily' => ['DAY', 'cron(0 12 * * ? *)'],
        ];
    }

    #[DataProvider('recurringFrequencies')]
    public function test_recurring_workflows_are_scheduled_with_a_cron_expression(string $frequency, string $expression): void
    {
        $this->givenScheduleGroup();
        $this->awsReturns(['ScheduleArn' => 'arn:schedule/10']);
        $this->workflowRepo->shouldReceive('update');

        $this->service->scheduleWorkflows([$this->workflow(dateTimeInfo: ['recurringFrequency' => $frequency])]);

        $this->assertSame($expression, $this->scheduledExpression());
    }

    public function test_with_in_workflows_are_scheduled_at_midnight_after_the_window(): void
    {
        $this->givenScheduleGroup();
        $this->awsReturns(['ScheduleArn' => 'arn:schedule/10']);
        $this->workflowRepo->shouldReceive('update');

        $this->service->scheduleWorkflows([$this->workflow(dateTimeInfo: ['executionEventIncident' => 'WITH_IN', 'executionFrequency' => '5', 'executionFrequencyType' => 'DAY'])]);

        $midnightInFiveDays = date('m/d/Y 00:00:00', strtotime('+5 days'));
        $this->assertSame('at('.convertLocalToUTC($midnightInFiveDays, 'm/d/Y H:i:s', 'America/New_York').')', $this->scheduledExpression());
    }

    public function test_custom_date_and_time_workflows_use_their_cron_fields(): void
    {
        $this->givenScheduleGroup();
        $this->awsReturns(['ScheduleArn' => 'arn:schedule/10']);
        $this->workflowRepo->shouldReceive('update');
        $cron = ['cronMinutes' => '30', 'cronHours' => '6', 'cronDayOfMonth' => '*', 'cronMonth' => '*', 'cronDayOfWeek' => '?', 'cronYear' => '*'];

        $this->service->scheduleWorkflows([$this->workflow('CUSTOM_DATE_AND_TIME', attributes: ['custom_date_time_info_to_execute_workflow' => $cron])]);

        $this->assertSame('cron(30 6 * * ? *)', $this->scheduledExpression());
    }

    public function test_an_already_scheduled_workflow_updates_its_schedule(): void
    {
        $this->givenScheduleGroup();
        $this->awsReturns([]);
        $this->workflowRepo->shouldReceive('update')->never();

        $this->service->scheduleWorkflows([$this->workflow(dateTimeInfo: ['recurringFrequency' => 'WEEK'], attributes: ['aws_event_bridge_arn' => 'arn:schedule/10'])]);

        $this->assertSame(['UpdateSchedule'], $this->awsCommandNames());
    }

    public function test_the_schedule_group_is_created_tagged_and_stored_on_first_use(): void
    {
        $this->configRepo->shouldReceive('getByKey')->andThrow(new \Exception('No query results'));
        $this->configRepo->shouldReceive('create')->once()->withArgs(fn (array $row) => $row['config_key'] === 'schedule_group_arn' && $row['config_value'] === self::GROUP_ARN);
        $this->awsReturns(['ScheduleGroups' => []], ['ScheduleGroupArn' => self::GROUP_ARN], [], ['ScheduleArn' => 'arn:schedule/10']);
        $this->workflowRepo->shouldReceive('update');

        $this->service->scheduleWorkflows([$this->workflow(dateTimeInfo: ['recurringFrequency' => 'WEEK'])]);

        $this->assertSame(['ListScheduleGroups', 'CreateScheduleGroup', 'TagResource', 'CreateSchedule'], $this->awsCommandNames());
        $this->assertSame([['Key' => 'type', 'Value' => 'workflow']], $this->awsCommands[2][1]['Tags']);
    }

    public function test_an_existing_schedule_group_in_aws_is_reused(): void
    {
        $this->configRepo->shouldReceive('getByKey')->andThrow(new \Exception('No query results'));
        $this->configRepo->shouldReceive('create')->once()->withArgs(fn (array $row) => $row['config_value'] === self::GROUP_ARN);
        $this->awsReturns(['ScheduleGroups' => [['Name' => self::GROUP, 'Arn' => self::GROUP_ARN]]], ['ScheduleArn' => 'arn:schedule/10']);
        $this->workflowRepo->shouldReceive('update');

        $this->service->scheduleWorkflows([$this->workflow(dateTimeInfo: ['recurringFrequency' => 'WEEK'])]);

        $this->assertSame(['ListScheduleGroups', 'CreateSchedule'], $this->awsCommandNames());
    }

    public function test_known_bug_a_failed_schedule_group_stops_every_remaining_workflow(): void
    {
        $this->configRepo->shouldReceive('getByKey')->andThrow(new \Exception('No query results'));
        $this->awsReturns('AccessDenied');

        $result = $this->service->scheduleWorkflows([
            $this->workflow(dateTimeInfo: ['recurringFrequency' => 'WEEK']),
            $this->workflow(dateTimeInfo: ['recurringFrequency' => 'MONTH'], attributes: ['id' => 11]),
        ]);

        $this->assertFalse($result);
        $this->assertSame(['ListScheduleGroups'], $this->awsCommandNames());
    }

    public function test_a_failed_schedule_is_logged_and_no_arn_is_stored(): void
    {
        $this->givenScheduleGroup();
        $this->awsReturns('ValidationException');
        $this->workflowRepo->shouldReceive('update')->never();

        $this->service->scheduleWorkflows([$this->workflow(dateTimeInfo: ['recurringFrequency' => 'WEEK'])]);

        Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_starts_with($message, 'Error creating schedule: Error creating schedule: ValidationException'));
    }

    public function test_known_bug_a_dated_workflow_without_a_date_or_frequency_errors(): void
    {
        $this->givenScheduleGroup();

        $this->expectExceptionMessage('Undefined variable $configureTimeForEventSchedulerToAwakeWorkflowSystem');

        $this->service->scheduleWorkflows([$this->workflow()]);
    }
}
