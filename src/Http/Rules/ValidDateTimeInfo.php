<?php

namespace Taurus\Workflow\Http\Rules;

use Closure;
use DateTime;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDateTimeInfo implements ValidationRule
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

        if ($effectiveAction === 'ON_DATE_TIME') {
            $allowedValues = [
                'executionFrequencyType' => ['DAY', 'MONTH', 'YEAR'],
                'executionEventIncident' => ['AFTER', 'BEFORE'],
                'executionEvent' => ['CREATION', 'EXPIRATION'],
                'recurringFrequency' => ['ONCE', 'MONTH', 'YEAR'],
            ];

            if (!is_int($value['executionFrequency'])) {
                $isValid = false;
                // $fail("Each executionFrequency must be an integer.");
            }

            foreach ($allowedValues as $key => $validOptions) {
                if (!in_array($value[$key], $validOptions, true)) {
                    $isValid = false;
                    // $fail("$key must be one of [" . implode(', ', $validOptions) . "].");
                }
            }

            // Validate executionEffectiveDate (must be a real date)
            $date = DateTime::createFromFormat('m/d/Y', $value['executionEffectiveDate']);
            if (!$date || $date->format('m/d/Y') !== $value['executionEffectiveDate']) {
                $isValid = false;
                // $fail("executionEffectiveDate must be a valid date in MM/DD/YYYY format.");
            }

            // Validate executionEffectiveTime (must be a real time in 24-hour format)
            $time = DateTime::createFromFormat('H:i', $value['executionEffectiveTime']);
            if (!$time || $time->format('H:i') !== $value['executionEffectiveTime']) {
                $isValid = false;
                // $fail("executionEffectiveTime must be a valid time in HH:MM 24-hour format.");
            }
        }

        if (!$isValid) {
            $fail("There are errors in the date and time information.");
        }
    }
}
