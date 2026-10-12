<?php

namespace Taurus\Workflow\Consumer\Common\GraphQL;

/**
 * Placeholders available to every module (mirrors `commonPlaceholders` in the
 * front-end placeholder list).
 *
 * Used by the base AbstractSchema of each consumer. The dispatch engines merge
 * getCommonFieldMapping() into every module's getFieldMapping(), so a placeholder
 * added here works in all modules without touching them. A module that maps the
 * same placeholder itself takes precedence.
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
     * Unsubscribe link on the app's `email/unsubscribe/{email}` route.
     * [email] is left in place, since the recipient is only known later - see
     * applyRecipientToCommonPlaceholders().
     */
    public function getUnsubscribeUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        if (! $baseUrl) {
            return '';
        }

        return "{$baseUrl}/api/email/unsubscribe/[email]?tenant=".rawurlencode((string) getTenant());
    }

    /**
     * Fills the recipient into common placeholders that depend on it, once the
     * email row has its `email` resolved. When a row has several recipients, the
     * first one is used.
     */
    public function applyRecipientToCommonPlaceholders(array $row): array
    {
        if (empty($row['UnsubscribeURL']) || ! str_contains($row['UnsubscribeURL'], '[email]')) {
            return $row;
        }

        $emails = array_values(array_filter((array) ($row['email'] ?? [])));

        $row['UnsubscribeURL'] = $emails
            ? str_replace('[email]', rawurlencode(trim($emails[0])), $row['UnsubscribeURL'])
            : '';

        return $row;
    }
}
