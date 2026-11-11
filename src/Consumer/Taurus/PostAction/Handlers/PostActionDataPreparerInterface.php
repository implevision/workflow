<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\Handlers;

interface PostActionDataPreparerInterface
{
    public static function prepare(array $payload, array $placeholders, string $messageId): array;
}
