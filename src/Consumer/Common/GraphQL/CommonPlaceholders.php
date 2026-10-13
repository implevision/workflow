<?php

namespace Taurus\Workflow\Consumer\Common\GraphQL;

use Taurus\Workflow\Consumer\Taurus\Helper;

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
