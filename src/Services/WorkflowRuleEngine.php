<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Http;

class WorkflowRuleEngine
{
    public $payload;

    public $ruleEngineResult;

    public $ruleSchema = [
        'comparison_operator' => '',
        'rule_value' => '',
        'context_value_to_compare' => '',
        'label' => '',
    ];

    public function prepare($entityData, $rules)
    {
        $payload = [];
        foreach ($rules as $rule) {
            $ruleSchema = $this->ruleSchema;

            $field = $rule['field'];
            $ruleSchema['comparison_operator'] = $rule['comparator'];
            $ruleSchema['rule_value'] = $rule['expectedValue'];
            $ruleSchema['context_value_to_compare'] = $entityData[$field];
            $ruleSchema['label'] = $field;

            $payload[] = $ruleSchema;
        }
        $this->payload = $payload;
    }

    public function validate()
    {
        $response = Http::withHeaders(['x-api-key' => config('workflow.rule_engine_client_key')])
            ->acceptJson()
            ->post(config('workflow.rule_engine_url'), $this->payload);

        if ($response->successful()) {
            $response = $response->json();

            return $response;
        } else {
            throw new \Exception('Error validating rule: '.$response->body());
        }
    }

    public function isAllRulesAreMatched() {}
}
