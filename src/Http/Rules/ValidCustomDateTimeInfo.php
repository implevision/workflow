<?php

namespace Taurus\Workflow\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCustomDateTimeInfo implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $effectiveAction = request()->input('when.effectiveActionToExecuteWorkflow');

        $isValid = true;

        if ($effectiveAction === 'CUSTOM_DATE_AND_TIME') {
            // Validate cron fields
            $cronFields = [
                'cronDayOfMonth',
                'cronDayOfWeek',
                'cronHours',
                'cronMinutes',
                'cronMonth',
                'cronYear',
            ];

            foreach ($cronFields as $cronField) {
                if (! isset($value[$cronField])) {
                    $isValid = false;
                    $fail("$cronField is required.");

                    continue;
                }
                // Accept only numbers or * for all except hours and minutes
                if (in_array($cronField, ['cronHours', 'cronMinutes'])) {
                    if (! preg_match('/^(\*|\d{1,2})$/', (string) $value[$cronField])) {
                        $isValid = false;
                        $fail("$cronField must be a number (0-59 for minutes, 0-23 for hours) or '*'.");
                    }
                } else {
                    if (! preg_match('/^(\*|\d{1,2})$/', (string) $value[$cronField])) {
                        $isValid = false;
                        $fail("$cronField must be a number (1-31 for day of month, 1-12 for month, 0-6 for day of week, or year) or '*'.");
                    }
                }
            }
        }

        if (! $isValid) {
            $fail('There are errors in the custom date and time information.');
        }
    }
}
