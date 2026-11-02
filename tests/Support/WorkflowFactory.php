<?php

namespace Taurus\Workflow\Tests\Support;

use Taurus\Workflow\Data\WorkflowData;

class WorkflowFactory
{
    public const ID = 1;

    public const MODULE = 'policy';

    public static function workflow(array $conditions = [], bool $isActive = true, string $effectiveAction = 'ON_RECORD_ACTION', array $dateTimeInfo = []): WorkflowData
    {
        return WorkflowData::fromArray([
            'id' => self::ID,
            'detail' => ['module' => self::MODULE, 'name' => 'Test workflow', 'isActive' => $isActive],
            'when' => [
                'effectiveActionToExecuteWorkflow' => $effectiveAction,
                'recordActionToExecuteWorkflow' => 'CREATE',
                'dateTimeInfoToExecuteWorkflow' => self::dateTimeInfo($dateTimeInfo),
            ],
            'workFlowConditions' => $conditions,
        ]);
    }

    public static function condition(array $actions, string $applyRuleTo = 'ALL', array $rules = [], bool $status = true, ?string $s3FilePath = null): array
    {
        return [
            'applyRuleTo' => $applyRuleTo,
            'applyConditionRules' => $rules,
            's3FilePath' => $s3FilePath,
            'status' => $status,
            'instanceActions' => $actions,
        ];
    }

    public static function rule(string $relation, string $comparator, string $expectedValue): array
    {
        return [
            'type' => 'group',
            'operator' => 'AND',
            'children' => [['type' => 'rule', 'relation' => $relation, 'comparator' => $comparator, 'expectedValue' => $expectedValue]],
        ];
    }

    public static function emailAction(array $payload = []): array
    {
        return ['actionType' => 'EMAIL', 'payload' => ['emailRecipient' => 'AgentEmail', ...$payload]];
    }

    public static function workflowOutputAction(array $payload = []): array
    {
        return ['actionType' => 'WORKFLOW_OUTPUT', 'payload' => $payload];
    }

    public static function dateTimeInfo(array $overrides = []): array
    {
        return [
            'certainDateTime' => false,
            'executionFrequency' => null,
            'executionFrequencyType' => null,
            'executionEventIncident' => null,
            'executionEvent' => null,
            'recurringFrequency' => null,
            'executionEffectiveDate' => null,
            'executionEffectiveTime' => null,
            ...$overrides,
        ];
    }
}
