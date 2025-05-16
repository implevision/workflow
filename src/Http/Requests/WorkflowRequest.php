<?php

namespace Taurus\Workflow\Http\Requests;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Taurus\Workflow\Http\Rules\ValidDateTimeInfo;
use Taurus\Workflow\Http\Rules\ValidRecordAction;
use Illuminate\Http\Exceptions\HttpResponseException;
use Taurus\Workflow\Http\Rules\ValidApplyConditionRules;

class WorkflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $tablePrefix = config('workflow.table_prefix', 'tb_taurus');
        $workflowTable = $tablePrefix . '_workflows';
        $workflowActionTable = $tablePrefix . '_workflow_actions';
        $workflowConditionTable =  $tablePrefix . '_workflow_conditions';
        return [
            'id' => 'sometimes|nullable|exists:' . $workflowTable . ',id',
            'detail.module' => 'required|string',
            'detail.name' => 'required|string',
            'detail.description' => 'nullable|string',
            'when.effectiveActionToExecuteWorkflow' => 'required|in:ON_RECORD_ACTION,ON_DATE_TIME',
            'when.recordActionToExecuteWorkflow' => ['nullable', new ValidRecordAction()],
            'when.dateTimeInfoToExecuteWorkflow' => ['nullable', new ValidDateTimeInfo()],
            'workFlowConditions' => 'required|array',
            'workFlowConditions.*.id' => 'sometimes|nullable|exists:' . $workflowConditionTable . ',id',
            'workFlowConditions.*.applyRuleTo' => 'required|string|in:ALL,CERTAIN,CUSTOM_FEED',
            'workFlowConditions.*.s3FilePath' => 'exclude_unless:workFlowConditions.*.applyRuleTo,CUSTOM_FEED|sometimes|string',
            'workFlowConditions.*.instanceActions' => 'required|array',
            'workFlowConditions.*.instanceActions.*.id' => 'sometimes|nullable|exists:' . $workflowActionTable . ',id',
            'workFlowConditions.*.instanceActions.*.actionType' => 'required|string|in:EMAIL',
            'workFlowConditions.*.instanceActions.*.payload' => 'required|array',
            'workFlowConditions.*.applyConditionRules' => [
                'required_if:workFlowConditions.*.applyRuleTo,CERTAIN',
                'array',
                new ValidApplyConditionRules(),
            ]
        ];
    }

    /**
     * Get the custom validation messages for the request.
     *
     * @return array Custom validation messages.
     */
    public function messages()
    {
        return [
            'detail.module.required' => 'The module field is required.',
            'detail.name.required' => 'The name field is required.',
            'when.effectiveActionToExecuteWorkflow.in' => 'The effectiveActionToExecuteWorkflow must be either ON_RECORD_ACTION or ON_DATE_TIME.',
            'workFlowConditions.applyConditionRules.required_if' => 'Condition rules are required when applyRuleTo is not ALL.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'errors'    => $validator->errors()
        ], Response::HTTP_BAD_REQUEST));
    }
}
