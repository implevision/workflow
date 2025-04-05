<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;
use Taurus\Workflow\Data\InstanceActionData;

class WorkflowConditionData extends Data
{
    public function __construct(
        public ?int $id,
        public string $applyRuleTo,
        public array $applyConditionRules,
        /** @var InstanceActionData[] */
        public array $instanceActions
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            applyRuleTo: $data['applyRuleTo'] ?? null,
            applyConditionRules: $data['applyConditionRules'] ?? [],
            instanceActions: InstanceActionData::collect($data['instanceActions'])
        );
    }
}
