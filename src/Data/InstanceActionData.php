<?php

namespace Taurus\Workflow\Data;

use Spatie\LaravelData\Data;

class InstanceActionData extends Data
{
    public function __construct(
        public ?int $id,
        public string $actionType,
        public array $payload
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            actionType: $data['actionType'],
            payload: $data['payload']
        );
    }

    public static function mapByActionType(array $actions): array
    {
        $mapped = [];

        foreach ($actions as $action) {
            if (isset($action['actionType'])) {
                $actionData = self::fromArray($action);
                if ($actionData instanceof InstanceActionData) {
                    $actionData = $actionData->toArray();
                }
                $mapped[$action['actionType']] = $actionData;
            }
        }

        return $mapped;
    }
}
