<?php

namespace Taurus\Workflow\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Taurus\Workflow\Models\JobWorkflow;

class JobWorkflowValidator
{
    public static function validate(array $record): void
    {
        $workflowTable = getTablePrefix().'_workflows';

        // workflow_id is nullable for manual workflow executions
        $workflowIdRules = isset($record['workflow_id']) && $record['workflow_id'] !== null
            ? ['required', 'integer', 'exists:'.$workflowTable.',id']
            : ['nullable', 'integer'];

        Validator::make($record, [
            'workflow_id' => $workflowIdRules,
            'status' => ['required', Rule::in(JobWorkflow::getAllowedStatuses())],
            'total_no_of_records_to_execute' => ['nullable', 'integer'],
            'total_no_of_records_executed' => ['nullable', 'integer'],
            'response' => ['nullable', 'array'],
        ])->validate();
    }
}
