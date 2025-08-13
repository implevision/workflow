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

        Validator::make($record, [
            'workflow_id' => ['required', 'integer', 'exists:'.$workflowTable.',id'],
            'status' => ['required', Rule::in(JobWorkflow::getAllowedStatuses())],
            'total_no_of_records_to_execute' => ['nullable', 'integer'],
            'total_no_of_records_executed' => ['nullable', 'integer'],
            'response' => ['nullable', 'array'],
        ])->validate();
    }
}
