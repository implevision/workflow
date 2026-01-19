<?php

namespace Taurus\Workflow\Consumer\Taurus;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class Helper
{
    public static function getHoldingCompanyDetail()
    {
        $holdingCompanyDetail = \DB::table('tb_holdingcompanies')->first();

        return [
            'logo' => $holdingCompanyDetail->logo_url,
            'public_logo' => $holdingCompanyDetail->public_logo_url,
            'wyo' => $holdingCompanyDetail->s_HoldingCompanyName,
            'naic_number' => $holdingCompanyDetail->naic_number,
        ];
    }

    public static function formatDate($dateToFormat)
    {
        return Carbon::parse($dateToFormat)->format('m/d/Y');
    }

    public static function createPortalURL($portal)
    {
        $hostURL = Config::get('app.url');
        $hostedDomain = self::extractDomainSimple($hostURL);

        switch ($portal) {
            case 'InsuredPortal':
                $hostedDomain = 'https://'.getTenant().'.mypolicy.'.$hostedDomain;
                break;
            case 'AgentPortal':
                $hostedDomain = 'https://'.getTenant().'.agent.'.$hostedDomain;
                break;
            case 'CorePortal':
                break;
        }

        return $hostedDomain;
    }

    public static function extractDomainSimple($url)
    {
        $parsedUrl = parse_url($url);

        if ($parsedUrl === false || ! isset($parsedUrl['host'])) {
            return false;
        }

        $host = $parsedUrl['host'];
        $hostArr = explode('.', $host);
        if (count($hostArr) < 2) {
            return false; // Not a valid domain
        }

        $domain = implode('.', array_slice($hostArr, -2));

        // Use regex to extract the domain pattern
        if (preg_match('/([^.]*\.)?'.$domain.'$/', $host, $matches)) {
            if (empty($matches[1])) {
                return $domain;
            } else {
                return rtrim($matches[1], '.').".{$domain}";
            }
        }

        // Fallback for non-odysseynext domains
        $hostParts = explode('.', $host);
        if (count($hostParts) >= 2) {
            return implode('.', array_slice($hostParts, -2));
        }

        return false;
    }

    public static function formatPhone($phoneNumber, $country = 'US')
    {
        // Remove all non-numeric characters from the input
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        switch (strtoupper($country)) {
            case 'US':
            default:
                return preg_replace('/(\\d{3})(\\d{3})(\\d{4})/', '($1) $2-$3', $phoneNumber);
        }
    }

    public static function generateDataImage($logoUrl)
    {
        if (empty($logoUrl)) {
            return '';
        }

        try {
            $urlData = parse_url($logoUrl);

            $mimeType = 'image/jpg';
            if (str_ends_with(strtolower($urlData['path']), 'png') == 'png') {
                $mimeType = 'image/png';
            } elseif (str_ends_with(strtolower($urlData['path']), 'jpeg') == 'jpeg') {
                $mimeType = 'image/jpeg';
            } elseif (str_ends_with(strtolower($urlData['path']), 'jpg') == 'jpg') {
                $mimeType = 'image/jpg';
            }

            $imgData = 'data:'.$mimeType.';base64, '.base64_encode(file_get_contents($logoUrl));
        } catch (\Exception $e) {
            return '';
        }

        return $imgData;
    }

    /**
     * Generate a pre-signed AWS S3 URL from a given file path.
     *
     * @param  string  $path  Path inside the S3 bucket (e.g. "amfam/2023/.../file.pdf")
     * @param  int  $expiry  Expiry time in minutes
     */
    public static function generatePresignedUrl(string $path, int $expiry = 60): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Ensure the S3 disk exists
        if (! Storage::disk('s3')) {
            return null;
        }

        // Generate pre-signed URL
        return Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes($expiry)
        );
    }

    /**
     * Get today's date formatted as m/d/Y.
     */
    public static function getTodaysDate(): string
    {
        return self::formatDate(Carbon::now());
    }

    /**
     * Get the display name for a given application code name from the database.
     *
     * @param  string  $appCodeName
     * @return string|null
     */
    public static function parseAppCodeNameToDisplayName($appCodeName)
    {
        $label = \DB::table('tb_appcodes')
            ->where('s_AppCodeName', $appCodeName)
            ->value('s_AppCodeNameForDisplay');

        return $label;
    }

    /**
     * Get the display name for a given application code name and dropdown group from the database.
     *
     * @param  string  $ddGroup
     * @param  string  $appCodeName
     * @return string|null
     */
    public static function parseAppCodeNameToDisplayNameUsingDDGroup($ddGroup, $appCodeName)
    {
        $label = \DB::table('tb_appcodes')
            ->where('tb_appcodetypes.s_AppCodeTypeName', $ddGroup)
            ->join(
                'tb_appcodetypes',
                'tb_appcodes.n_AppCodeTypeId_FK',
                '=',
                'tb_appcodetypes.n_AppCodeTypeId_PK'
            )
            ->where('s_AppCodeName', $appCodeName)
            ->value('s_AppCodeNameForDisplay');

        return $label;
    }

    /**
     * Convert a YES/NO/Y/N value to a display-friendly string ('Yes', 'No', or '').
     *
     * @param  mixed  $value
     * @return string
     */
    public static function parseYesNoDisplayName($value)
    {
        $normalized = strtoupper(trim((string) $value));

        if ($normalized === 'YES' || $normalized === 'Y') {
            return 'Yes';
        } elseif ($normalized === 'NO' || $normalized === 'N') {
            return 'No';
        } else {
            return '';
        }
    }

    /**
     * Formats a number to US dollar currency format (e.g., $1,234.56)
     *
     * @param  float|int|string  $amount
     */
    public static function formatCurrency($amount)
    {
        if (! is_numeric($amount)) {
            return '';
        }

        return '$'.number_format((float) $amount, 2);
    }

    /**
     * Formats a number with grouped thousands (e.g., 1,234,567)
     *
     * @param  float|int|string  $number
     */
    public static function formatNumber($number)
    {
        if (! is_numeric($number)) {
            return '';
        }

        return number_format((float) $number);
    }
}
