<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\Handlers;

use Taurus\Workflow\Consumer\Taurus\PostAction\UploadAsDocument\PrepareUploadAsDocumentData;
use Taurus\Workflow\Consumer\Taurus\PostAction\UploadAsDocument\UploadAsDocumentService;

class UploadAsDocumentHandler implements PostActionHandlerInterface
{
    public function prepare(array $payload, array $placeholders, string $messageId): array
    {
        return PrepareUploadAsDocumentData::prepare($payload, $placeholders, $messageId);
    }

    public function execute(string $module, array $payload, array $preparedData): mixed
    {
        return UploadAsDocumentService::execute($module, $payload, $preparedData);
    }
}
