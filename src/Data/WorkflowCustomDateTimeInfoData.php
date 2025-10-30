<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;

class WorkflowCustomDateTimeInfoData extends Data
{
    public function __construct(
        public ?string $cronMinutes,
        public ?string $cronHours,
        public ?string $cronDayOfMonth,
        public ?string $cronMonth,
        public ?string $cronDayOfWeek,
        public ?string $cronYear
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cronMinutes: $data['cronMinutes'] ?? null,
            cronHours: $data['cronHours'] ?? null,
            cronDayOfMonth: $data['cronDayOfMonth'] ?? null,
            cronMonth: $data['cronMonth'] ?? null,
            cronDayOfWeek: $data['cronDayOfWeek'] ?? null,
            cronYear: $data['cronYear'] ?? null
        );
    }
}
