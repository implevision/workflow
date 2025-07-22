<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;

class WorkflowDetailData extends Data
{
    public function __construct(
        public string $module,
        public string $name,
        public ?string $description,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            module: $data['module'],
            name: $data['name'],
            description: $data['description'] ?? null,
            isActive: $data['isActive'] ?? true
        );
    }
}
