<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;

class WorkflowConditionData extends Data
{
    public function __construct(
        public ?int $id,
        public string $applyRuleTo,
        public ?string $s3FilePath,
        public array $applyConditionRules,
        /** @var array<string, InstanceActionData> */
        public array $instanceActions
    ) {}

    public static function collect(mixed $items, ?string $into = null): mixed
    {
        // laravel-data v3 uses `collection()`, v4 uses `collect()`
        if (method_exists(parent::class, 'collection')) {
            return parent::collection($items)->toArray();
        }

        return parent::collect($items, $into);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            applyRuleTo: $data['applyRuleTo'] ?? null,
            applyConditionRules: $data['applyConditionRules'] ?? [],
            s3FilePath: $data['s3FilePath'] ?? null,
            instanceActions: InstanceActionData::mapByActionType($data['instanceActions'])
        );
    }
}
