<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\Handlers;

use Taurus\Workflow\Consumer\Taurus\PostAction\SaveClaimLetter\PrepareSaveClaimLetterData;
use Taurus\Workflow\Consumer\Taurus\PostAction\SaveClaimLetter\SaveClaimLetterService;

class SaveClaimLetterHandler extends AbstractPostActionHandler
{
    protected array $preparers = [
        'WORKFLOW_OUTPUT' => PrepareSaveClaimLetterData::class,
    ];

    public function execute(string $module, array $payload, array $preparedData): mixed
    {
        return SaveClaimLetterService::execute($module, $payload, $preparedData);
    }
}
