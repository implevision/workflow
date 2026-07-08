<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\Handlers;

interface PostActionHandlerInterface
{
    public function prepare(array $payload, array $placeholders, string $messageId): array;

    public function execute(string $module, array $payload, array $preparedData): mixed;
}
