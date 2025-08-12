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

    'aws_profile' => env('AWS_PROFILE'),

    'aws_region' => env('AWS_DEFAULT_REGION', 'us-east-1'),

    'aws_bucket' => env('WORKFLOW_AWS_BUCKET', env('AWS_BUCKET')),

    'table_prefix' => env('WORKFLOW_TABLE_PREFIX', 'tb_taurus'),

    'timezone' => env('WORKFLOW_TIMEZONE_TO_USE', 'America/New_York'),

    'aws_lambda_function_arn_to_invoke_workflow' => env('AWS_LAMBDA_FUNCTION_ARN_TO_INVOKE_WORKFLOW', 'arn:aws:lambda:us-east-1:358884819536:function:uat1-run-command'),

    'aws_iam_role_arn_to_invoke_lambda_from_event_bridge' => env('AWS_IAM_ROLE_ARN_TO_INVOKE_LAMBDA_FROM_EVENT_BRIDGE', 'arn:aws:iam::358884819536:role/RoleForEventsToInvokeLambda'),

    'task_definition' => env('WORKFLOW_TASK_DEFINITION', 'uat-gfs-saas-core-worker'),

    'is_workflow_live' => false,

    'single_tenant' => env('WORKFLOW_SINGLE_TENANT'),

    'rule_engine_url' => env('WORKFLOW_RULE_ENGINE_URL'),

    'rule_engine_client_key' => env('WORKFLOW_RULE_ENGINE_CLIENT_KEY'),

    'email_template_service_url' => env('WORKFLOW_EMAIL_TEMPLATE_SERVICE_URL'),

    'email_template_service_client_key' => env('WORKFLOW_EMAIL_TEMPLATE_SERVICE_CLIENT_KEY'),

    'sender_email_address' => env('WORKFLOW_SENDER_EMAIL_ADDRESS'),

    'required_actions' => [
        'sns:CreateTopic' => 'To create a new SNS topic, the user must have permission to create it.',
        'iam:CreateRole' => 'To create a new IAM role, the user must have permission to create it.',
        'scheduler:ListScheduleGroups' => 'Before creating a new schedule group, it is necessary to list them in order to check for duplication.',
        'scheduler:CreateScheduleGroup' => 'To attach with a schedule, a new schedule group must be created.',
        'scheduler:CreateSchedule' => 'To create a new schedule, the user must have permission to create it in order to invoke the workflow at particular time.',
        'ses:SendEmail' => 'Allows sending single emails.',
        'ses:SendBulkEmail' => 'Allows sending bulk emails (via SendBulkEmail API).',
    ],

    'current_consumer' => env('WORKFLOW_CURRENT_CONSUMER', 'taurus'),

    'graphql' => [
        'endpoint' => env('GRAPHQL_ENDPOINT', 'http://127.0.0.1:8000/graphql'),
        'timeout' => env('GRAPHQL_TIMEOUT', 30),
        'headers' => [
            'User-Agent' => 'Laravel GraphQL Client',
        ],
    ],

    'bucket_to_save_email_letters' => env('WORKFLOW_BUCKET_TO_SAVE_EMAIL_LETTERS'),

    'default_system_user_id' => env('WORKFLOW_DEFAULT_SYSTEM_USER_ID', 1),

    'email_queue' => env('WORKFLOW_EMAIL_QUEUE'),

    'post_action_queue' => env('WORKFLOW_POST_ACTION_QUEUE'),

    'allowed_receiver' => [
        'email' => ['unique.jimish@gmail.com'],
        'ends_with' => ['@thinktaurus.com'],
    ],

    'send_all_workflow_email_to' => 'unique.jimish@gmail.com',
];
