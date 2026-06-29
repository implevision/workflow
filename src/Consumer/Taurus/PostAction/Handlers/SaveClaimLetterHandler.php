<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\Handlers;

use Taurus\Workflow\Consumer\Taurus\PostAction\SaveClaimLetter\SaveClaimLetterService;
use Taurus\Workflow\Consumer\Taurus\PostAction\SaveClaimLetter\PrepareSaveClaimLetterData;

class SaveClaimLetterHandler implements PostActionHandlerInterface
{
    public function prepare(array $payload, array $placeholders, string $messageId): array
    {
        return PrepareSaveClaimLetterData::prepare($payload, $placeholders, $messageId);
    }

    public function execute(string $module, array $payload, array $preparedData): mixed
    {
        return SaveClaimLetterService::execute($module, $payload, $preparedData);
    }
}
