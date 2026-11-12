<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction;

use Taurus\Workflow\Consumer\Taurus\PostAction\Handlers\SaveClaimLetterHandler;
use Taurus\Workflow\Consumer\Taurus\PostAction\Handlers\UploadAsDocumentHandler;

class PostActionService
{
    private array $handlers = [
        'uploadAsDocument' => UploadAsDocumentHandler::class,
        'saveClaimLetter' => SaveClaimLetterHandler::class,
    ];

    public function execute($module, $payload, $messageId): array
    {
        $postAction = $payload['postAction'] ?? null;

        if (! $postAction) {
            throw new \InvalidArgumentException('Post action is required.');
        }

        $handlerClass = $this->handlers[$postAction] ?? null;

        if (! $handlerClass) {
            throw new \InvalidArgumentException("Unknown post action: {$postAction}");
        }

        $handler = new $handlerClass;
        $data = $payload['payload'];
        unset($payload['payload']);

        $results = [];
        foreach ($data as $placeholders) {
            $preparedData = $handler->prepare($payload, $placeholders, $messageId);
            $results[] = $handler->execute($module, $payload, $preparedData);
        }

        return $results;
    }
}
