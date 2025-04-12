<?php

namespace Taurus\Workflow\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRecordAction implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validActions = ['CREATE', 'EDIT', 'CREATE_OR_EDIT', 'FIELD_UPDATE', 'DELETE'];
        $effectiveAction = request()->input('when.effectiveActionToExecuteWorkflow');
        $stringValidAction = implode(', ', $validActions);

        if ($effectiveAction === 'ON_RECORD_ACTION' && !in_array($value, $validActions)) {
            $fail("Invalid value for recordActionToExecuteWorkflow. Allowed values: [$stringValidAction].");
        }
    }
}
