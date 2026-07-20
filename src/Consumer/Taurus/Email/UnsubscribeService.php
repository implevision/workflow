<?php

namespace Taurus\Workflow\Consumer\Taurus\Email;

use Taurus\Workflow\Models\EmailUnsubscribe;

class UnsubscribeService
{
    /**
     * Filter out email addresses that have unsubscribed.
     *
     * @param  array  $emails  List of recipient email addresses.
     * @param  array  $context  Optional context, may contain 'campaign_type'.
     * @return array The emails that are still subscribed.
     */
    public function removeUnsubscribed(array $emails, array $context = []): array
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

        $campaignType = $context['campaign_type'] ?? null;

        // Both the email address AND the campaign_type must match for a recipient
        // to be treated as unsubscribed. Without a campaign_type there is nothing
        // to match against, so no one is removed.
        if (empty($campaignType)) {
            return $emails;
        }

        $unsubscribed = EmailUnsubscribe::query()
            ->whereIn('email', $normalized)
            ->where('campaign_type', $campaignType)
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->flip();

        if ($unsubscribed->isEmpty()) {
            return $emails;
        }

        $remaining = array_values(array_filter(
            $emails,
            fn ($email) => ! $unsubscribed->has(strtolower(trim((string) $email)))
        ));

        $removedCount = count($emails) - count($remaining);
        if ($removedCount > 0) {
            \Log::info('WORKFLOW - Skipped '.$removedCount.' recipient(s) as they have unsubscribed from "'.$campaignType.'" emails.', [
                'campaign_type' => $campaignType,
                'unsubscribed_emails' => $unsubscribed->keys()->all(),
                'context' => $context,
            ]);
        }

        return $remaining;
    }
}
