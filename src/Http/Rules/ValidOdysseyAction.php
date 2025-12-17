<?php

namespace Taurus\Workflow\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidOdysseyAction implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $effectiveAction = request()->input('when.effectiveActionToExecuteWorkflow');

        if ($effectiveAction === 'ODYSSEY_ACTION') {
            if (
                ! is_array($value) ||
                ! isset($value['odysseyActionSubmodule']) ||
                ! is_string($value['odysseyActionSubmodule']) ||
                trim($value['odysseyActionSubmodule']) === ''
            ) {
                $fail('The Submodule field in odyssey actions is required and cannot be empty.');
            }
        }
    }
}
