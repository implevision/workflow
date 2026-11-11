<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\Handlers;

use Taurus\Workflow\Consumer\Taurus\PostAction\UploadAiAnalysis\PrepareUploadAiAnalysisData;
use Taurus\Workflow\Consumer\Taurus\PostAction\UploadAsDocument\PrepareUploadAsDocumentData;
use Taurus\Workflow\Consumer\Taurus\PostAction\UploadAsDocument\UploadAsDocumentService;

class UploadAsDocumentHandler extends AbstractPostActionHandler
{
    protected array $preparers = [
        '*' => PrepareUploadAsDocumentData::class,
        'ANALYZE_WITH_AI' => PrepareUploadAiAnalysisData::class,
    ];

    public function execute(string $module, array $payload, array $preparedData): mixed
    {
        return UploadAsDocumentService::execute($module, $payload, $preparedData);
    }
}
