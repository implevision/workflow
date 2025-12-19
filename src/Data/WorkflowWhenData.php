<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;

class WorkflowWhenData extends Data
{
    public function __construct(
        public string $effectiveActionToExecuteWorkflow,
        public ?string $recordActionToExecuteWorkflow,
        public WorkflowDateTimeInfoData $dateTimeInfoToExecuteWorkflow,
        public WorkflowCustomDateTimeInfoData $customDateTimeInfoToExecuteWorkflow,
        public WorkflowOdysseyActionData $odysseyActionToExecuteWorkflow,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            effectiveActionToExecuteWorkflow: $data['effectiveActionToExecuteWorkflow'],
            recordActionToExecuteWorkflow: $data['recordActionToExecuteWorkflow'],
            dateTimeInfoToExecuteWorkflow: WorkflowDateTimeInfoData::from($data['dateTimeInfoToExecuteWorkflow']),
            customDateTimeInfoToExecuteWorkflow: WorkflowCustomDateTimeInfoData::fromArray($data['customDateTimeInfoToExecuteWorkflow'] ?? []),
            odysseyActionToExecuteWorkflow: WorkflowOdysseyActionData::fromArray($data['odysseyActionToExecuteWorkflow'] ?? []),
        );
    }
}
