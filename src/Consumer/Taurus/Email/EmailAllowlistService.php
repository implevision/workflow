<?php

namespace Taurus\Workflow\Consumer\Taurus\Email;

use Illuminate\Support\Facades\DB;

class EmailAllowlistService
{
    /**
     * Return only the allowed email addresses (those that have NOT unsubscribed).
     *
     * @param  array  $emails  List of recipient email addresses.
     * @return array The allowed (still-subscribed) emails.
     */
    public function getAllowed(array $emails): array
    {
        if (empty($emails)) {
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
            \Log::info('WORKFLOW - Skipped '.$removedCount.' recipient(s) as they have unsubscribed from emails.', [
                'unsubscribed_emails' => $unsubscribed->keys()->all(),
            ]);
        }

        return $allowed;
    }
}
