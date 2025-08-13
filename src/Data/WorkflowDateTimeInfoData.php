<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;

class WorkflowDateTimeInfoData extends Data
{
    public function __construct(
        public bool $certainDateTime,
        public ?string $executionFrequency,
        public ?string $executionFrequencyType,
        public ?string $executionEventIncident,
        public ?string $executionEvent,
        public ?string $recurringFrequency,
        public ?string $executionEffectiveDate,
        public ?string $executionEffectiveTime
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            certainDateTime: $data['certainDateTime'] ?? false,
            executionFrequency: $data['executionFrequency'] ?? null,
            executionFrequencyType: $data['executionFrequencyType'] ?? null,
            executionEventIncident: $data['executionEventIncident'] ?? null,
            executionEvent: $data['executionEvent'] ?? null,
            recurringFrequency: $data['recurringFrequency'] ?? null,
            executionEffectiveDate: $data['executionEffectiveDate'] ?? null,
            executionEffectiveTime: $data['executionEffectiveTime' ?? null]
        );
    }
}
