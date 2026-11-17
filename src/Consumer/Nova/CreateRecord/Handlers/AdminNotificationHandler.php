<?php

namespace Taurus\Workflow\Consumer\Nova\CreateRecord\Handlers;

use App\Models\DocumentDetail;
use App\Services\ReportNotificationService;
use Taurus\Workflow\Consumer\Common\CreateRecord\CreateRecordHandlerInterface;

/**
 * Notifies the tenant's administrators, in Nova's claims page bell, that an
 * adjuster added a report to a claim. Who is notified (and whether anyone is)
 * is Nova's ReportNotificationService; this only maps the action onto it.
 *
 * Expected (resolved) payload keys:
 *   documentId - document_details.id of the report (required, positive integer)
 */
class AdminNotificationHandler implements CreateRecordHandlerInterface
{
    public function execute(array $payload, array $context): mixed
    {
        // Validate before casting: (int) would turn "123abc" into 123.
        $documentId = filter_var($payload['documentId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($documentId === false) {
            throw new \InvalidArgumentException('documentId must be a positive integer to notify administrators.');
        }

        return app(ReportNotificationService::class)->notifyAdmins(DocumentDetail::findOrFail($documentId));
    }
}
