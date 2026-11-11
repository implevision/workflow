<?php

namespace Taurus\Workflow\Services\WorkflowActions\Helpers\Email;

class EmailAddressValidator
{
    /**
     * A recipient value is a comma-separated string or a list of addresses (one entry may itself
     * be comma-separated). Returns the trimmed, non-empty addresses.
     */
    public static function split($recipients): array
    {
        $emails = [];

        foreach ((array) $recipients as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            foreach (explode(',', (string) $value) as $email) {
                $email = trim($email);

                if ($email !== '') {
                    $emails[] = $email;
                }
            }
        }

        return $emails;
    }

    public static function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Returns [validEmails, invalidEmails].
     */
    public static function partition($recipients): array
    {
        $valid = [];
        $invalid = [];

        foreach (self::split($recipients) as $email) {
            if (self::isValid($email)) {
                $valid[] = $email;
            } else {
                $invalid[] = $email;
            }
        }

        return [$valid, $invalid];
    }
}
