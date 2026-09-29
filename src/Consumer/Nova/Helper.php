<?php

namespace Taurus\Workflow\Consumer\Nova;

use App\Helpers\AwsS3Helper;
use App\Models\HoldingCompany;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
     *
     * Read through the model, never DB::table: nova puts every tenant in one
     * database and separates them with a global scope, so a raw query returns
     * whichever row is first and hands one tenant another tenant's branding.
     */
    public static function getHoldingCompanyDetail(): ?object
    {
        return HoldingCompany::first();
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
            return null;
        }
    }

    /**
     * Pre-signed S3 URL for a stored file, or null if it cannot be built.
     *
     * @param  string  $path  Key inside the bucket
     * @param  int  $expiry  Expiry in minutes
     */
    public static function generatePresignedUrl(string $path, int $expiry = 60): ?string
    {
        if (empty($path)) {
            return null;
        }

        try {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes($expiry));
        } catch (\Throwable $e) {
            Log::error('NOVA_WORKFLOW: failed to presign', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Embeddable URL for the tenant's logo, or '' when there is none.
     *
     * Nova keeps branding in tb_holdingcompanies rather than on the claim
     * graph, so there is nothing to pull through GraphQL. AwsS3Helper owns
     * the bucket rules, so the lookup is delegated rather than repeated here.
     */
    public static function parseCompanyLogo(): string
    {
        return AwsS3Helper::companyLogoUrl();
    }
}
