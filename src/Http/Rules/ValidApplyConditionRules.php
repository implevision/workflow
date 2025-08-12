<?php

namespace Taurus\Workflow\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ValidApplyConditionRules implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Extract parent index from attribute name
        $parentIndex = Str::between($attribute, 'workFlowConditions.', '.applyConditionRules') ?? null;

        $allowedComparators = ['LESS_THAN', 'EQUALS', 'GREATER_THAN'];

        // Get the `applyRuleTo` value for this index
        $applyRuleTo = \request()->input("workFlowConditions.$parentIndex.applyRuleTo");

        // If applyRuleTo is not 'ALL', validate each condition
        if ($applyRuleTo !== 'ALL') {
            foreach ($value as $condition) {
                if (empty($condition['field']) || (empty($condition['comparator']) || ! in_array($condition['comparator'], $allowedComparators)) || empty($condition['expectedValue'])) {
                    $fail('Each applyConditionRule must have [field, comparator, and expectedValue].');
                }
            }
        }
    }
}
