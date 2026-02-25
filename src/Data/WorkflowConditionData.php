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
        public array $instanceActions
    ) {}

    /**
     * Compatible with laravel-data v3 and v4.
     *
     * DO NOT ADD "@var array<string, InstanceActionData|array>" to instanceActions — breaks v4 transformer.
     */
    public static function collect(mixed $items, ?string $into = null): array
    {
        return array_map(
            fn ($item) => static::fromArray((array) $item),
            (array) $items
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            applyRuleTo: $data['applyRuleTo'] ?? '',
            applyConditionRules: $data['applyConditionRules'] ?? [],
            s3FilePath: $data['s3FilePath'] ?? null,
            instanceActions: InstanceActionData::mapByActionType(
                $data['instanceActions'] ?? []
            )
        );
    }
}
