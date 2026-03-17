<?php

namespace Taurus\Workflow\Console\Commands\Seeders;

use Taurus\Workflow\Console\Commands\Seeders\Contracts\SeederValueResolverInterface;
use Illuminate\Support\Facades\Log;

class WorkflowSeederFormatter
{
    /** @var array<string, SeederValueResolverInterface> */
    protected array $resolvers = [];

    /**
     * Register a resolver for a given placeholder type.
     *
     * Example: registerResolver('product_by_code', new ProductByCodeResolver())
     * This allows "{{product_by_code@NFIP}}" in seed JSON to be resolved at seeding time.
     */
    public function registerResolver(string $type, SeederValueResolverInterface $resolver): void
    {
        $this->resolvers[$type] = $resolver;
    }

    /**
     * Walk the full workflow data array and replace any {{type@argument}} placeholders
     * in condition rule expectedValue fields with their resolved values.
     */
    public function format(array $data): array
    {
        if (empty($data['workFlowConditions'])) {
            return $data;
        }

        $data['workFlowConditions'] = $this->processConditions($data['workFlowConditions']);

        return $data;
    }

    private function processConditions(array $conditions): array
    {
        foreach ($conditions as &$condition) {
            if (! empty($condition['applyConditionRules'])) {
                $condition['applyConditionRules']["children"] = $this->processRules($condition['applyConditionRules']["children"]);
            }
        }

        return $conditions;
    }

    /**
     * Recursively process rules and groups (handles nested children).
     */
    private function processRules(array $rules): array
    {
        foreach ($rules as &$rule) {
            if ($rule['type'] === 'rule') {
                if (isset($rule['expectedValue']) && is_string($rule['expectedValue'])) {
                    $rule['expectedValue'] = $this->resolveValue($rule['expectedValue']);
                }
            } elseif ($rule['type'] === 'group' && ! empty($rule['children'])) {
                $rule['children'] = $this->processRules($rule['children']);
            }
        }

        return $rules;
    }

    /**
     * Parse a "{{type@argument}}" placeholder and dispatch to the matching resolver.
     * Values that do not match the placeholder pattern are returned unchanged.
     */
    private function resolveValue(string $value): mixed
    {
        if (! preg_match('/^\{\{([^@}]+)@(.+)\}\}$/', $value, $matches)) {
            return $value;
        }

        [, $type, $argument] = $matches;

        if (! isset($this->resolvers[$type])) {
            Log::warning("WORKFLOW_SEEDER_FORMATTER - No resolver registered for type: {$type}");

            return $value;
        }

        return $this->resolvers[$type]->resolve($argument);
    }
}
