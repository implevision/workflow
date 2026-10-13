<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL;

use Taurus\Workflow\Consumer\Taurus\Helper;

/**
 * Placeholders available to every Taurus module (mirrors `commonPlaceholders`
 * in the front-end placeholder list).
 *
 * Kept per consumer, since values like the unsubscribe link depend on the
 * consumer's portals. Used by the Taurus AbstractSchema. The dispatch engines merge
 * getCommonFieldMapping() into every Taurus module's getFieldMapping(), so a
 * placeholder added here works in all Taurus modules without touching them. A
 * module that maps the same placeholder itself takes precedence.
 *
 * Taurus only for now: Nova's AbstractSchema does not use this trait, so these
 * placeholders are not supported there (they resolve as unmapped fields).
 */
trait CommonPlaceholders
{
    /**
     * Field mapping for the common placeholders, same shape as a module's getFieldMapping().
     */
    public function getCommonFieldMapping(): array
    {
        return [
            'UnsubscribeURL' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'getUnsubscribeUrl',
            ],
        ];
    }

    /**
     * Unsubscribe link on the agent portal `/email/unsubscribe` page, which calls
     * the core unsubscribe API and shows the result.
     * [email] is left in place, since the recipient is only known later - see
     * applyRecipientToCommonPlaceholders().
     */
    public function getUnsubscribeUrl(): string
    {
        if (! Helper::extractDomainSimple(config('app.url'))) {
            return '';
        }

        return Helper::createPortalURL('AgentPortal')
            .'/email/unsubscribe?type=workflow&tenant='.rawurlencode((string) getTenant()).'&email=[email]';
    }

    /**
     * Fills the recipient into common placeholders that depend on it, once the
     * email row has its `email` resolved. Since a row is sent as one email to all
     * its recipients, a row with several recipients is split into one row per
     * recipient, so each gets their own unsubscribe link.
     *
     * @return array[] the rows to send, at least one
     */
    public function applyRecipientToCommonPlaceholders(array $row): array
    {
        if (empty($row['UnsubscribeURL']) || ! str_contains($row['UnsubscribeURL'], '[email]')) {
            return [$row];
        }

        $emails = array_values(array_filter(array_map('trim', (array) ($row['email'] ?? []))));

        if (! $emails) {
            $row['UnsubscribeURL'] = '';

            return [$row];
        }

        return array_map(function (string $email) use ($row) {
            $row['email'] = [$email];
            $row['UnsubscribeURL'] = str_replace('[email]', rawurlencode($email), $row['UnsubscribeURL']);

            return $row;
        }, $emails);
    }
}
