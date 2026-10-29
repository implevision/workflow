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
            // Count only - recipient addresses are not written to the logs
            Log::info('WORKFLOW - Skipped '.$removedCount.' recipient(s) as they have unsubscribed from emails.');
        }

        return $allowed;
    }

    /**
     * Promotional CSV rows never pass through the dispatcher's unsubscribe filter, so they
     * are checked here with one lookup per batch: rows whose recipients have all
     * unsubscribed are dropped, the rest keep only their still-subscribed addresses.
     *
     * @param  array  $rows  CSV rows, each with an `email` column.
     * @return array The rows that still have someone to send to.
     */
    public function applyEmailSupressedByConsumerFilterOnRows(array $rows): array
    {
        $rowEmails = array_map($this->splitEmails(...), $rows);
        $subscribed = $this->applyEmailSupressedByConsumerFilter(array_merge(...$rowEmails), true);

        $filteredRows = [];
        foreach ($rows as $key => $row) {
            // A row without an email is kept as is - SES skips it itself
            if ($rowEmails[$key]) {
                $kept = array_intersect($rowEmails[$key], $subscribed);

                // Every recipient of this row has unsubscribed
                if (! $kept) {
                    continue;
                }
                // Kept as an array: SES casts `email` to ToAddresses, where a joined string is one invalid address
                $row['email'] = array_values($kept);
            }
            $filteredRows[] = $row;
        }

        $skippedRows = count($rows) - count($filteredRows);
        if ($skippedRows) {
            Log::info("WORKFLOW - Skipped {$skippedRows} CSV row(s) as all their recipients have unsubscribed from emails");
        }

        return $filteredRows;
    }

    /**
     * A CSV email cell may hold several comma-separated addresses.
     */
    private function splitEmails(array $row): array
    {
        $emails = array_map('trim', explode(',', (string) ($row['email'] ?? '')));

        return array_values(array_filter($emails, fn ($email) => $email !== ''));
    }
}
