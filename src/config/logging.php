<?php

return [

    'channels' => [

        'workflow' => [
            'driver' => 'daily',
            'path' => storage_path('logs/workflow.log'),
            'level' => 'debug',
            'days' => 14,
        ],

    ],

];
