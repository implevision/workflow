<?php

namespace Taurus\Workflow\Consumer\Taurus;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

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
                $hostedDomain = 'https://mypolicy.'.tenant('id').'.'.$hostedDomain;
                break;
            case 'AgentPortal':
                $hostedDomain = 'https://agent.'.tenant('id').'.'.$hostedDomain;
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
}
