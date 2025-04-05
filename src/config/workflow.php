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

    'table_prefix' => env('tbl_taurus'),

    'required_actions' => [
        'sns:CreateTopic',
        'iam:CreateRole'
    ],
];
