<?php

namespace Taurus\Workflow\Consumer\Nova;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generic formatting shared by nova's schema classes. Module-specific parsing
 * stays in the schema class that needs it; only formatting every module could
 * reuse belongs here.
 */
class Helper
{
    /**
     * The tenant's branding row. Nova writes branding to tb_holdingcompanies,
     * not tb_companies -- the latter's logo_url is null for every tenant.
     */
    public static function getHoldingCompanyDetail(): ?object
    {
        return DB::table('tb_holdingcompanies')->first();
    }

    /**
     * Name of the adjusting firm this tenant operates as, or '' if unset.
     */
    public static function adjustingFirmName(): string
    {
        return (string) (self::getHoldingCompanyDetail()?->s_HoldingCompanyName ?? '');
    }

    /**
     * Contact number for the adjusting firm, or '' if unset.
     */
    public static function adjustingFirmPhone(): string
    {
        return (string) (self::getHoldingCompanyDetail()?->phone_no ?? '');
    }

    /**
     * m/d/Y, or null when there is nothing to format. Callers decide what an
     * empty date should read as ('N/A', '', ...).
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
