<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;

class WorkflowOdysseyActionData extends Data
{
    public function __construct(
        public string $odysseyActionSubmodule = '',
        public ?int $odysseyActionSubmodulePk = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            odysseyActionSubmodule: $data['odysseyActionSubmodule'] ?? '',
            odysseyActionSubmodulePk: $data['odysseyActionSubmodulePk'] ?? null
        );
    }
}
