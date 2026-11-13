<?php

namespace Taurus\Workflow\Consumer\Nova\CreateRecord;

use Taurus\Workflow\Consumer\Common\CreateRecord\AbstractCreateRecordService;
use Taurus\Workflow\Consumer\Nova\CreateRecord\Handlers\AdminNotificationHandler;

class CreateRecordService extends AbstractCreateRecordService
{
    protected function handlers(): array
    {
        return [
            'adminNotification' => AdminNotificationHandler::class,
        ];
    }
}
