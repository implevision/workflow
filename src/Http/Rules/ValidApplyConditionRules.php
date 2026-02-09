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

        // If applyRuleTo is 'CERTAIN', validate each condition recursively
        if ($applyRuleTo === 'CERTAIN') {
            foreach ($value as $i => $condition) {
                $this->validateRuleOrGroup($condition, $fail, $allowedComparators, $conditionIndex, $i + 1, []);
            }
        }
    }

    /**
     * Recursively validate a rule or group condition.
     *
     * @param  array  $condition The condition to validate, can be a rule or a group.
     * @param  Closure  $fail The validation failure callback.
     * @param  array  $allowedComparators The list of allowed comparators.
     * @param  int  $conditionIndex The 1-based index of the parent condition.
     * @param  int|null  $ruleNumber The 1-based index of the rule within its condition or group, if applicable.
     * @param  array  $groupPath The hierarchical path of parent groups for building error messages.
     * @return void
     */
    private function validateRuleOrGroup($condition, $fail, $allowedComparators, $conditionIndex, $ruleNumber = null, $groupPath = [])
    {
        if (! isset($condition['type'])) {
            return;
        }
        if ($condition['type'] === 'rule') {
            $missingFields = [];
            if (
                ! isset($condition['field']) ||
                $condition['field'] === '' ||
                $condition['field'] === null
            ) {
                $missingFields[] = 'field';
            }

            if (
                ! isset($condition['comparator']) ||
                $condition['comparator'] === '' ||
                ! in_array($condition['comparator'], $allowedComparators)
            ) {
                $missingFields[] = 'comparator';
            }

            if (
                ! $this->isExpectedValueAllowedToBeEmptyForGivenRule($condition)
                && (! array_key_exists('expectedValue', $condition)
                    || $condition['expectedValue'] === null
                    || (
                        is_string($condition['expectedValue']) &&
                        trim($condition['expectedValue']) === ''
                    )
                )
            ) {
                $missingFields[] = 'expected value';
            }

            if (! empty($missingFields)) {
                $groupStr = '';
                if (! empty($groupPath)) {
                    $groupStr = ' in group '.implode(' > ', $groupPath);
                }
                $fail('The following fields are required for rule '.$ruleNumber.$groupStr.' in condition '.$conditionIndex.': '.implode(', ', $missingFields).'.');
            }
        } elseif ($condition['type'] === 'group' && isset($condition['children']) && is_array($condition['children'])) {
            // Add this group index to the path
            $currentGroupPath = array_merge($groupPath, [$ruleNumber]);
            foreach ($condition['children'] as $i => $child) {
                $this->validateRuleOrGroup($child, $fail, $allowedComparators, $conditionIndex, $i + 1, $currentGroupPath);
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
