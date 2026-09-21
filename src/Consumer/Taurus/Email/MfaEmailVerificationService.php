<?php

namespace Taurus\Workflow\Consumer\Taurus\Email;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MfaEmailVerificationService
{
    /**
     * Number of days an MFA email verification stays valid.
     */
    private const TOTAL_VALID_DAYS = 365;

    /**
     * Check whether a user's MFA email verification is still valid.
     *
     * Workflow runs without an authenticated user, so the user is resolved
     * from the given email address instead of Auth::user().
     *
     * @param  string  $email  The user's email address.
     * @return array Status, validity, warning flag/message and days remaining.
     */
    public function isMFAEmailVerificationValid(string $email): array
    {
        try {
            $userRow = DB::table('tb_users')
                ->where('Email', $email)
                ->first();

            if (! $userRow) {
                return ['status' => false, 'message' => 'User not found'];
            }

            $graceRow = DB::table('tb_holdingcompanies')->first();
            if (! $graceRow) {
                return ['status' => false, 'message' => 'No holding company record found'];
            }

            $graceMetadata = json_decode($graceRow->metadata, true) ?: [];
            $graceDays = $graceMetadata['grace_email_verification_agent_days'] ?? 0;
            $userMetadata = json_decode($userRow->metadata, true) ?: [];
            $email = $userRow->Email;
            $isValid = false;
            $showWarning = false;
            $warningMessage = '';
            $daysRemaining = 0;

            // Check if user has an MFA email update date
            if (! empty($userMetadata['mfa_email_updated_date'])) {
                $updatedDate = Carbon::parse($userMetadata['mfa_email_updated_date']);
                $daysSinceUpdate = $updatedDate->diffInDays(now());
                $totalGraceAndValidDays = self::TOTAL_VALID_DAYS + $graceDays;

                if ($daysSinceUpdate < self::TOTAL_VALID_DAYS) {
                    $isValid = true;
                } elseif ($daysSinceUpdate < $totalGraceAndValidDays) {
                    // Within grace period
                    $showWarning = true;
                    $daysRemaining = max(0, $totalGraceAndValidDays - (int) $daysSinceUpdate);
                    $warningMessage = "You have {$daysRemaining} days to complete email verification.";
                }
            }

            return [
                'status' => true,
                'valid' => $isValid,
                'showWarningMessage' => $showWarning,
                'warningMessage' => $warningMessage,
                'email' => $email,
                'daysRemaining' => $daysRemaining,
            ];
        } catch (\Exception $e) {
            Log::error('Error checking MFA email verification status: '.$e->getMessage());

            return [
                'status' => false,
                'message' => 'An error occurred while checking MFA email verification status.',
            ];
        }
    }
}
