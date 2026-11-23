<?php

namespace Taurus\Workflow\Tests\Consumer\Taurus\Event;

use Taurus\Workflow\Consumer\Taurus\Event\EventPlaceholderService;
use Taurus\Workflow\Tests\Consumer\Taurus\TestCase;

class EventPlaceholderServiceTest extends TestCase
{
    public function test_document_uploaded_requires_and_mandates_the_uploaded_document(): void
    {
        $service = new EventPlaceholderService;

        $this->assertSame(['AttachUploadedDocument'], $service->getListOfRequiredData('DocumentUploaded'));
        $this->assertSame(['AttachUploadedDocument'], $service->getListOfMandateData('DocumentUploaded'));
    }

    public function test_other_events_add_nothing(): void
    {
        $service = new EventPlaceholderService;

        foreach (['CREATE', 'UPDATE', null] as $event) {
            $this->assertSame([], $service->getListOfRequiredData($event));
            $this->assertSame([], $service->getListOfMandateData($event));
        }
    }
}
