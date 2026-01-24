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
        $conditionIndex = $parentIndex + 1;

        // Get the `applyRuleTo` value for this index
        $applyRuleTo = \request()->input("workFlowConditions.$parentIndex.applyRuleTo");

        $comparatorsConfig = config('workflowBaseData.baseData.comparator', []);
        $allowedComparators = array_merge(
            array_keys($comparatorsConfig['string'] ?? []),
            array_keys($comparatorsConfig['int'] ?? []),
            array_keys($comparatorsConfig['boolean'] ?? [])
        );

        // If applyRuleTo is 'CERTAIN', validate each condition
        if ($applyRuleTo === 'CERTAIN') {
            foreach ($value as $condition) {
                if ($condition['type'] === 'rule') {
                    if (
                        ! isset($condition['field']) ||
                        $condition['field'] === '' ||
                        $condition['field'] === null
                    ) {
                        $fail('A valid field value for rules is required for condition '.$conditionIndex);
                    }

                    if (
                        ! isset($condition['comparator']) ||
                        $condition['comparator'] === '' ||
                        ! in_array($condition['comparator'], $allowedComparators)
                    ) {
                        $fail('A valid comparator value for rules is required for condition '.$conditionIndex);
                    }

                    if (
                        ! $this->isExpectedValueAllowedToBeEmptyForGivenRule($condition)
                        && (! array_key_exists('expectedValue', $condition)
                            || $condition['expectedValue'] === null
                            || (is_string($condition['expectedValue']) && trim($condition['expectedValue']) === '')
                        )
                    ) {
                        $fail('A valid expected value for rules is required for condition '.$conditionIndex);
                    }
                }
            }
        }
    }

    /**
     * Determine if a rule's comparator allows an empty expected value.
     *
     * @param  array  $rule  The rule array, must contain 'comparator' key.
     * @return bool True if the comparator allows empty expected value, false otherwise.
     */
    public function isExpectedValueAllowedToBeEmptyForGivenRule($rule)
    {
        if (! is_array($rule) || empty($rule['comparator'])) {
            return false;
        }
        static $emptyAllowedComparators = ['IS_NULL', 'IS_NOT_NULL'];

        return in_array($rule['comparator'], $emptyAllowedComparators, true);
    }
}
