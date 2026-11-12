<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\Handlers;

abstract class AbstractPostActionHandler
{
    /** @var array<string, class-string<PostActionDataPreparerInterface>> actionType => preparer, optional '*' is the fallback */
    protected array $preparers = [];

    public function prepare(array $payload, array $placeholders, string $messageId): array
    {
        $actionType = $payload['actionType'] ?? '';
        $preparer = $this->preparers[$actionType] ?? $this->preparers['*']
            ?? throw new \InvalidArgumentException(static::class." does not support action type: {$actionType}");

        return $preparer::prepare($payload, $placeholders, $messageId);
    }

    abstract public function execute(string $module, array $payload, array $preparedData): mixed;
}
