<?php

namespace Taurus\Workflow\Consumer\Taurus\Email;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailSupressedByConsumerService
{
    /**
     * Return only the allowed email addresses (those that have NOT unsubscribed).
     * Only promotional emails are checked; transactional ones go to every recipient.
     *
     * @param  array  $emails  List of recipient email addresses.
     * @param  bool  $isPromotionalEmail  Whether the email template is promotional.
     * @return array The allowed (still-subscribed) emails.
     */
    public function applyEmailSupressedByConsumerFilter(array $emails, bool $isPromotionalEmail = false): array
    {
        if (! $isPromotionalEmail || empty($emails)) {
            return $emails;
        }

        $normalized = array_values(array_filter(array_map(
            fn ($email) => strtolower(trim((string) $email)),
            $emails
        )));

        if (empty($normalized)) {
            return $emails;
        }

        $unsubscribed = DB::table('email_unsubscribes')
            ->whereIn('email', $normalized)
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->flip();

        if ($unsubscribed->isEmpty()) {
            return $emails;
        }

        $allowed = array_values(array_filter(
            $emails,
            fn ($email) => ! $unsubscribed->has(strtolower(trim((string) $email)))
        ));

        $removedCount = count($emails) - count($allowed);
        if ($removedCount > 0) {
            Log::info('WORKFLOW - Skipped '.$removedCount.' recipient(s) as they have unsubscribed from emails.', [
                'unsubscribed_emails' => $unsubscribed->keys()->all(),
            ]);
        }

        return $allowed;
    }
}
