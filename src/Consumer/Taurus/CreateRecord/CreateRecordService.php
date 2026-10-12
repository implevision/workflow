<?php

namespace Taurus\Workflow\Consumer\Taurus\CreateRecord;

use Taurus\Workflow\Consumer\Common\CreateRecord\AbstractCreateRecordService;
use Taurus\Workflow\Consumer\Taurus\CreateRecord\Handlers\AgentTaskHandler;

class CreateRecordService extends AbstractCreateRecordService
{
    protected function handlers(): array
    {
        return [
            'agentTask' => AgentTaskHandler::class,
        ];
    }
}
