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
        /** @var array<string, InstanceActionData|array> */
        public array $instanceActions
    ) {}

    /**
     * Compatible with laravel-data v3 and v4
     */
    public static function collect(mixed $items, ?string $into = null): mixed
    {
        if (is_callable([parent::class, 'collect'])) {
            return parent::collect($items, $into);
        }

        if (is_callable([parent::class, 'collection'])) {
            return parent::collection($items)->toArray();
        }

        return $items;
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