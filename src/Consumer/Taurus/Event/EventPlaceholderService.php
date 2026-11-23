<?php

namespace Taurus\Workflow\Consumer\Taurus\Event;

/**
 * Placeholders a Taurus workflow needs because of the event that triggered it,
 * on top of whatever its actions ask for.
 *
 * Example: a workflow fired by DocumentUploaded always carries the uploaded
 * document (AttachUploadedDocument), and must not run without it.
 */
class EventPlaceholderService
{
    private const REQUIRED_DATA_BY_EVENT = [
        'DocumentUploaded' => ['AttachUploadedDocument'],
    ];

    private const MANDATE_DATA_BY_EVENT = [
        'DocumentUploaded' => ['AttachUploadedDocument'],
    ];

    public function getListOfRequiredData(?string $event): array
    {
        return self::REQUIRED_DATA_BY_EVENT[$event] ?? [];
    }

    public function getListOfMandateData(?string $event): array
    {
        return self::MANDATE_DATA_BY_EVENT[$event] ?? [];
    }
}
