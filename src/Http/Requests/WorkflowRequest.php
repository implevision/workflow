<?php

namespace Taurus\Workflow\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Taurus\Workflow\Http\Rules\ValidApplyConditionRules;
use Taurus\Workflow\Http\Rules\ValidCustomDateTimeInfo;
use Taurus\Workflow\Http\Rules\ValidDateTimeInfo;
use Taurus\Workflow\Http\Rules\ValidInstanceActions;
use Taurus\Workflow\Http\Rules\ValidRecordAction;

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
        $tablePrefix = getTablePrefix();
        $workflowTable = $tablePrefix.'_workflows';
        $workflowActionTable = $tablePrefix.'_workflow_actions';
        $workflowConditionTable = $tablePrefix.'_workflow_conditions';

        $actionTypes = ['EMAIL', 'CREATE_TASK', 'CREATE_RECORD', 'WEB_HOOK'];

        $rules = [
            'id' => 'sometimes|nullable|exists:'.$workflowTable.',id',
            'detail.module' => 'required|string',
            'detail.name' => 'required|string',
            'detail.description' => 'nullable|string',
            'when.effectiveActionToExecuteWorkflow' => 'required|in:ON_RECORD_ACTION,ON_DATE_TIME,CUSTOM_DATE_AND_TIME,ODYSSEY_ACTION',
            'when.recordActionToExecuteWorkflow' => ['nullable', new ValidRecordAction],
            'when.dateTimeInfoToExecuteWorkflow' => ['nullable', new ValidDateTimeInfo],
            'when.customDateTimeInfoToExecuteWorkflow' => ['nullable', new ValidCustomDateTimeInfo],
            'when.odysseyActionToExecuteWorkflow' => 'required_if:when.effectiveActionToExecuteWorkflow,ODYSSEY_ACTION|string',
            'workFlowConditions' => 'required|array',
            'workFlowConditions.*.id' => 'sometimes|nullable|exists:'.$workflowConditionTable.',id',
            'workFlowConditions.*.applyRuleTo' => 'required|string|in:ALL,CERTAIN,CUSTOM_FEED',
            'workFlowConditions.*.s3FilePath' => 'exclude_unless:workFlowConditions.*.applyRuleTo,CUSTOM_FEED|sometimes|string',
            // 'workFlowConditions.*.instanceActions' => 'required|array',
            // 'workFlowConditions.*.instanceActions.*.id' => 'sometimes|nullable|exists:' . $workflowActionTable . ',id',
            // 'workFlowConditions.*.instanceActions.*.actionType' => 'required|string|in:EMAIL',
            // 'workFlowConditions.*.instanceActions.*.payload' => 'required|array',
            'workFlowConditions.*.instanceActions' => ['required', 'array', new ValidInstanceActions],
            'workFlowConditions.*.applyConditionRules' => [
                'required_if:workFlowConditions.*.applyRuleTo,CERTAIN',
                'array',
            ],
            'workFlowConditions.*.applyConditionRules.type' => 'required|in:group,rule',
            'workFlowConditions.*.applyConditionRules.operator' => 'required|in:AND,OR',
            'workFlowConditions.*.applyConditionRules.id' => 'sometimes|nullable|string',
            'workFlowConditions.*.applyConditionRules.children' => [
                'array',
                new ValidApplyConditionRules,
            ],
        ];

        foreach ($actionTypes as $type) {
            $rules["workFlowConditions.*.instanceActions.$type.id"] = 'sometimes|nullable|exists:'.$workflowActionTable.',id';
            $rules["workFlowConditions.*.instanceActions.$type.actionType"] = "required_with:workFlowConditions.*.instanceActions.$type|string|in:$type";
            $rules["workFlowConditions.*.instanceActions.$type.payload"] = "required_with:workFlowConditions.*.instanceActions.$type|array";
        }

        return $rules;
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
            'when.effectiveActionToExecuteWorkflow.in' => 'The effectiveActionToExecuteWorkflow must be either ON_RECORD_ACTION, ON_DATE_TIME, CUSTOM_DATE_AND_TIME, or ODYSSEY_ACTION.',
            'workFlowConditions.applyConditionRules.required_if' => 'Condition rules are required when applyRuleTo is not ALL.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], Response::HTTP_BAD_REQUEST));
    }
}
