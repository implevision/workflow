<?php

namespace Taurus\Workflow\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidInstanceActions implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if it's an array and at least one value is not null
        if (!(is_array($value) && collect($value)->filter()->isNotEmpty())) {
            \Log::info($value);
            $fail('At least one instance action (EMAIL, CREATE_TASK, CREATE_RECORD, or WEB_HOOK) must be defined.');
        }
    }
}
