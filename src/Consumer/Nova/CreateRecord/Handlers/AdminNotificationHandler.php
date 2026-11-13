<?php

namespace Taurus\Workflow\Consumer\Nova\CreateRecord\Handlers;

use App\Models\DocumentDetail;
use App\Services\ReportNotificationService;
use Taurus\Workflow\Consumer\Common\CreateRecord\CreateRecordHandlerInterface;

/**
 * Notifies the tenant's administrators, in Nova's header bell, that a report
 * was added to a claim. Recipients, the actor and the notification itself are
 * Nova's ReportNotificationService; this only maps the action onto it.
 *
 * Expected (resolved) payload keys:
 *   documentId - document_details.id of the report (required)
 */
class AdminNotificationHandler implements CreateRecordHandlerInterface
{
    public function execute(array $payload, array $context): mixed
    {
        $documentId = (int) ($payload['documentId'] ?? 0);

        if (! $documentId) {
            throw new \InvalidArgumentException('documentId is required to notify administrators.');
        }

        return app(ReportNotificationService::class)->notifyAdmins(DocumentDetail::findOrFail($documentId));
    }
}
