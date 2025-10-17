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
                'cronMinutes' => [
                    'label' => 'Minutes',
                    'regex' => '/^([*]|[0-5]?\d)(-([0-5]?\d))?(\/\d+)?(,([*]|[0-5]?\d)(-([0-5]?\d))?(\/\d+)?)*$/',
                    'error' => 'Minutes must be a valid cron expression (0-59, *, ranges, lists, steps).',
                ],
                'cronHours' => [
                    'label' => 'Hours',
                    'regex' => '/^([*]|[01]?\d|2[0-3])(-([01]?\d|2[0-3]))?(\/\d+)?(,([*]|[01]?\d|2[0-3])(-([01]?\d|2[0-3]))?(\/\d+)?)*$/',
                    'error' => 'Hours must be a valid cron expression (0-23, *, ranges, lists, steps).',
                ],
                'cronDayOfMonth' => [
                    'label' => 'Day of Month',
                    'regex' => '/^([*?]|[1-9]|[12]\d|3[01])(-([1-9]|[12]\d|3[01]))?(\/\d+)?(,([*?]|[1-9]|[12]\d|3[01])(-([1-9]|[12]\d|3[01]))?(\/\d+)?)*$/',
                    'error' => 'Day of Month must be a valid cron expression (1-31, *, ?, ranges, lists, steps).',
                ],
                'cronMonth' => [
                    'label' => 'Month',
                    'regex' => '/^([*]|[1-9]|1[0-2])(-([1-9]|1[0-2]))?(\/\d+)?(,([*]|[1-9]|1[0-2])(-([1-9]|1[0-2]))?(\/\d+)?)*$/',
                    'error' => 'Month must be a valid cron expression (1-12, *, ranges, lists, steps).',
                ],
                'cronDayOfWeek' => [
                    'label' => 'Day of Week',
                    'regex' => '/^([*?]|[0-6])(-[0-6])?(\/\d+)?(,([*?]|[0-6])(-[0-6])?(\/\d+)?)*$/',
                    'error' => 'Day of Week must be a valid cron expression (0-6, *, ?, ranges, lists, steps).',
                ],
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
