<?php

namespace Taurus\Workflow\Validators;

use Illuminate\Validation\Rule;
use Taurus\Workflow\Models\JobWorkflow;
use Illuminate\Support\Facades\Validator;

class JobWorkflowValidator
{
    public static function validate(array $record): void
    {
        Validator::make($record, [
            'workflow_id' => ['required', 'integer', 'exists:tbl_workflows,id'],
            'status' => ['required', Rule::in(JobWorkflow::getAllowedStatuses())],
            'total_no_of_records_to_execute' => ['nullable', 'integer'],
            'total_no_of_records_executed' => ['nullable', 'integer'],
            'response' => ['nullable', 'array'],
        ])->validate();
    }
}
