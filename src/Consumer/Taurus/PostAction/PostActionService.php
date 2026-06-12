<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction;

use Taurus\Workflow\Consumer\Taurus\PostAction\Handlers\UploadAsDocumentHandler;

class PostActionService
{
    private array $handlers = [
        'uploadAsDocument' => UploadAsDocumentHandler::class,
    ];

    public function execute($module, $payload, $messageId): void
    {
        $postAction = $payload['postAction'] ?? null;

        if (! $postAction) {
            throw new \InvalidArgumentException('Post action is required.');
        }

        $handlerClass = $this->handlers[$postAction] ?? null;

        if (! $handlerClass) {
            throw new \InvalidArgumentException("Unknown post action: {$postAction}");
        }

        $handler = new $handlerClass();
        $data = $payload['payload'];
        unset($payload['payload']);

        foreach ($data as $placeholders) {
            $preparedData = $handler->prepare($payload, $placeholders, $messageId);
            $handler->execute($module, $payload, $preparedData);
        }
    }
}
