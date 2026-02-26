<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

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
     */
    public static function collect(mixed $items, ?string $into = null): array|DataCollection
    {

        // Compatible with laravel-data v3
        if (is_callable([parent::class, 'collection'])) {
            return parent::collection($items)->toArray();
        }

        // Compatible with laravel-data v4
        return parent::collect($items, $into);
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
