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

        if ($effectiveAction === 'CUSTOM_DATE_AND_TIME') {
            $cronFields = [
                // Minutes: 0-59, *, ranges, lists, steps
                // Examples: '5', '0-10', '*/15', '5,10,15', '*', '0-59/5'
                // Not allowed: '60', 'a', '-1', '5-60/2'
                'cronMinutes' => [
                    'label' => 'Minutes',
                    'regex' => '/^([*]|[0-5]?\d)(-([0-5]?\d))?(\/\d+)?(,([*]|[0-5]?\d)(-([0-5]?\d))?(\/\d+)?)*$/',
                    'error' => 'Minutes must be a valid cron expression (0-59, *, ranges, lists, steps).',
                ],
                // Hours: 0-23, *, ranges, lists, steps
                // Examples: '0', '12', '0-23', '*/2', '1,5,10', '*', '0-23/3'
                // Not allowed: '24', 'a', '-1', '0-24/2'
                'cronHours' => [
                    'label' => 'Hours',
                    'regex' => '/^([*]|[01]?\d|2[0-3])(-([01]?\d|2[0-3]))?(\/\d+)?(,([*]|[01]?\d|2[0-3])(-([01]?\d|2[0-3]))?(\/\d+)?)*$/',
                    'error' => 'Hours must be a valid cron expression (0-23, *, ranges, lists, steps).',
                ],
                // Day of Month: 1-31, *, ?, ranges, lists, steps
                // Examples: '1', '15', '1-31', '*/5', '1,15,30', '*', '?', '1-31/2'
                // Not allowed: '0', '32', 'a', '-1', '1-32/2'
                'cronDayOfMonth' => [
                    'label' => 'Day of Month',
                    'regex' => '/^([*?]|[1-9]|[12]\d|3[01])(-([1-9]|[12]\d|3[01]))?(\/\d+)?(,([*?]|[1-9]|[12]\d|3[01])(-([1-9]|[12]\d|3[01]))?(\/\d+)?)*$/',
                    'error' => 'Day of Month must be a valid cron expression (1-31, *, ?, ranges, lists, steps).',
                ],
                // Month: 1-12, *, ranges, lists, steps
                // Examples: '1', '12', '1-12', '*/3', '1,6,12', '*', '1-12/2'
                // Not allowed: '0', '13', 'a', '-1', '1-13/2'
                'cronMonth' => [
                    'label' => 'Month',
                    'regex' => '/^([*]|[1-9]|1[0-2])(-([1-9]|1[0-2]))?(\/\d+)?(,([*]|[1-9]|1[0-2])(-([1-9]|1[0-2]))?(\/\d+)?)*$/',
                    'error' => 'Month must be a valid cron expression (1-12, *, ranges, lists, steps).',
                ],
                // Day of Week: 0-6, *, ?, ranges, lists, steps
                // Examples: '0', '6', '0-6', '*/2', '0,3,6', '*', '?', '0-6/2'
                // Not allowed: '7', 'a', '-1', '0-7/2'
                'cronDayOfWeek' => [
                    'label' => 'Day of Week',
                    'regex' => '/^([*?]|[0-6])(-[0-6])?(\/\d+)?(,([*?]|[0-6])(-[0-6])?(\/\d+)?)*$/',
                    'error' => 'Day of Week must be a valid cron expression (0-6, *, ?, ranges, lists, steps).',
                ],
                // Year: 4-digit, *, ranges, lists, steps
                // Examples: '2025', '2025-2030', '*/2', '2025,2026,2027', '*', '2025-2030/2'
                // Not allowed: '20', 'abcd', '2025-20/2'
                'cronYear' => [
                    'label' => 'Year',
                    'regex' => '/^([*]|\d{4})(-\d{4})?(\/\d+)?(,([*]|\d{4})(-\d{4})?(\/\d+)?)*$/',
                    'error' => 'Year must be a valid cron expression (4-digit, *, ranges, lists, steps).',
                ],
            ];

            foreach ($cronFields as $field => $meta) {
                if (!isset($value[$field])) {
                    $fail("Value of {$meta['label']} is required.");
                    continue;
                }
                if (!preg_match($meta['regex'], (string)$value[$field])) {
                    $fail($meta['error']);
                }
            }
        }
    }
}
