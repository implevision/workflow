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

            foreach (self::tokenize((string) $value) as $email) {
                $email = trim($email);

                if ($email !== '') {
                    $emails[] = $email;
                }
            }
        }

        return $emails;
    }

    /**
     * Splits on commas, except inside a quoted string ("x,y"@example.com), where a backslash
     * escapes the next character. A plain explode would turn "x,victim@example.net,y"@example.com
     * into a separate, valid victim@example.net recipient. An unclosed quote runs to the end of
     * the value, so the rest stays one (invalid) address rather than becoming new recipients.
     *
     * Byte-wise is safe for UTF-8: '"', ',' and '\' never occur inside a multi-byte character.
     */
    private static function tokenize(string $value): array
    {
        $tokens = [];
        $token = '';
        $inQuotes = false;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($inQuotes && $char === '\\' && $i + 1 < $length) {
                $token .= $char.$value[++$i];

                continue;
            }

            if ($char === '"') {
                $inQuotes = ! $inQuotes;
            } elseif ($char === ',' && ! $inQuotes) {
                $tokens[] = $token;
                $token = '';

                continue;
            }

            $token .= $char;
        }

        $tokens[] = $token;

        return $tokens;
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
