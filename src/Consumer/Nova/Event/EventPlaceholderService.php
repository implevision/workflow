<?php

namespace Taurus\Workflow\Consumer\Nova\Event;

/**
 * Placeholders a Nova workflow needs because of the event that triggered it.
 * Nova has no event specific placeholders.
 */
class EventPlaceholderService
{
    public function getListOfRequiredData(?string $event): array
    {
        return [];
    }

    public function getListOfMandateData(?string $event): array
    {
        return [];
    }
}
