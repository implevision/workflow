<?php

namespace Taurus\Workflow\Consumer\Nova;

use Carbon\Carbon;

/**
 * Generic formatting shared by nova's schema classes, mirroring
 * Consumer\Taurus\Helper. Module-specific parsing stays in the schema class
 * that needs it; only formatting every module could reuse belongs here.
 */
class Helper
{
    /**
     * m/d/Y, or null when there is nothing to format. Callers decide what an
     * empty date should read as ('N/A', '', ...), same as Taurus's Helper.
     */
    public static function formatDate($dateToFormat): ?string
    {
        if (empty($dateToFormat)) {
            return null;
        }

        try {
            return Carbon::parse($dateToFormat)->format('m/d/Y');
        } catch (\Throwable) {
            return (string) $dateToFormat;
        }
    }

    /**
     * Formats a number to US dollar currency format (e.g. $1,234.56), or ''
     * when the value is not numeric.
     */
    public static function formatCurrency($amount): string
    {
        if (! is_numeric($amount)) {
            return '';
        }

        return '$'.number_format((float) $amount, 2);
    }

    /**
     * Flattens a GraphQL Address into display lines: street, then locality.
     * Returns an empty list when there is no address, so callers can decide
     * what to render in its place.
     *
     * @param  array<string, mixed>|null  $address
     * @return array<int, string>
     */
    public static function formatAddressLines(?array $address): array
    {
        if (! $address) {
            return [];
        }

        $street = trim(implode(' ', array_filter([
            $address['addressLine1'] ?? '',
            $address['addressLine2'] ?? '',
        ])));

        $postal = trim((string) ($address['postalCode'] ?? ''));
        $suffix = trim((string) ($address['postalCodeSuffix'] ?? ''));

        $locality = trim(implode(' ', array_filter([
            $address['city'] ?? '',
            $address['state'] ?? '',
            $suffix ? $postal.'-'.$suffix : $postal,
        ])));

        return array_values(array_filter([$street, $locality]));
    }
}
