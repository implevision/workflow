<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    |
    | This value is the name of your modules, which will be exposed to configure
    | workflow. `fields` key is used to define the fields of the module on which 
    | workflow can trigger.
    |
    */

    'aws_profile' => env('AWS_PROFILE', 'default'),

    'aws_region' => env('AWS_DEFAULT_REGION', 'us-east-1'),

    'aws_bucket' => env('WORKFLOW_AWS_BUCKET', env('AWS_BUCKET')),

    'table_prefix' => env('WORKFLOW_TABLE_PREFIX', 'tb_taurus'),

    'timezone' => env('WORKFLOW_TIMEZONE_TO_USE', 'America/New_York'),

    'aws_lambda_function_arn_to_invoke_workflow' => env('AWS_LAMBDA_FUNCTION_ARN_TO_INVOKE_WORKFLOW'),

    'aws_iam_role_arn_to_invoke_lambda_from_event_bridge' => env('AWS_IAM_ROLE_ARN_TO_INVOKE_LAMBDA_FROM_EVENT_BRIDGE'),

    'is_workflow_live' => false,

    'single_tenant' => env('WORKFLOW_SINGLE_TENANT'),

    'required_actions' => [
        'sns:CreateTopic' => "To create a new SNS topic, the user must have permission to create it.",
        'iam:CreateRole' => "To create a new IAM role, the user must have permission to create it.",
        'scheduler:ListScheduleGroups' => "Before creating a new schedule group, it is necessary to list them in order to check for duplication.",
        'scheduler:CreateScheduleGroup' => "To attach with a schedule, a new schedule group must be created.",
        'scheduler:CreateSchedule' => "To create a new schedule, the user must have permission to create it in order to invoke the workflow at particular time.",
        'ses:SendEmail' => "Allows sending single emails.",
        'ses:SendBulkEmail' => "Allows sending bulk emails (via SendBulkEmail API).",
    ],
];
