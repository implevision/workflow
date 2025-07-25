<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction;

class PostActionService
{
    public function execute($module, $payload, $messageId)
    {
        try {
            $data = $payload['payload'];
            unset($payload['payload']);
            foreach ($data as $placeholders) {
                $preparedData = $this->prepareData($payload, $placeholders, $messageId);
                $this->executePostAction($module, $payload, $preparedData);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function prepareData($payload, $placeholders, $messageId)
    {
        $actionType = $payload['actionType'] ?? null;

        if (!$actionType) {
            throw new \InvalidArgumentException('Action type is required for post action execution.');
        }

        if ($actionType == 'BulkEmail') {
            return \Taurus\Workflow\Consumer\Taurus\PostAction\PrepareBulkEmailData::prepare($payload, $placeholders, $messageId);
        }

        return [];
    }

    private function executePostAction($module, $payload, $preparedData)
    {
        $postAction = $payload['postAction'] ?? [];

        if (empty($postAction)) {
            throw new \InvalidArgumentException('Post action is required for execution.');
        }

        if ($postAction == 'uploadAsDocument') {
            return \Taurus\Workflow\Consumer\Taurus\PostAction\UploadAsDocument::execute($module, $payload, $preparedData);
        }

        return false;
    }
}
