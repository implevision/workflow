<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;
use Taurus\Workflow\Data\WorkflowConditionData;

class WorkflowData extends Data
{
    public function __construct(
        public ?int $id,
        public ?string $awsEventBridgeArn,
        public WorkflowDetailData $detail,
        public WorkflowWhenData $when,
        /** @var WorkflowConditionData[] */
        public array $workFlowConditions
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            awsEventBridgeArn: $data['awsEventBridgeArn'] ?? null,
            detail: WorkflowDetailData::from($data['detail']),
            when: WorkflowWhenData::from($data['when']),
            workFlowConditions: WorkflowConditionData::collect($data['workFlowConditions'] ?? [])
        );
    }
}
