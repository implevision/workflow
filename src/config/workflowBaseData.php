<?php

function getComparatorLabel($key)
{
    $labels = [
        '==' => '==',
        '!=' => '!=',
        'includes' => 'includes',
        'startsWith' => 'starts with',
        'endsWith' => 'ends with',
        'isNull' => 'is null',
        'isNotNull' => 'is not null',
        'isEmpty' => 'is empty',
        'isNotEmpty' => 'is not empty',
        '<' => '<',
        '<=' => '<=',
        '>' => '>',
        '>=' => '>=',
    ];

    return $labels[$key] ?? $key;
}

return [
    'modules' => [
        /*[
            'label' => "User",
            'model' => "\App\User",
            'fields' => [
                'email' => ['Label' => 'Email', 'type' => 'string'],
                'first_name' => ['Label' => 'First Name', 'type' => 'string'],
                'last_name' => ['Label' => 'Last Name', 'type' => 'string'],
            ]
        ]*/],
    'comparator' => [
        'string' => [
            '==' => getComparatorLabel('=='),
            '!=' => getComparatorLabel('!='),
            'includes' => getComparatorLabel('includes'),
            'startsWith' => getComparatorLabel('startsWith'),
            'endsWith' => getComparatorLabel('endsWith'),
            'isNull' => getComparatorLabel('isNull'),
            'isNotNull' => getComparatorLabel('isNotNull'),
            'isEmpty' => getComparatorLabel('isEmpty'),
            'isNotEmpty' => getComparatorLabel('isNotEmpty'),
        ],
        'int' => [
            '==' => getComparatorLabel('=='),
            '!=' => getComparatorLabel('!='),
            '<' => getComparatorLabel('<'),
            '<=' => getComparatorLabel('<='),
            '>' => getComparatorLabel('>'),
            '>=' => getComparatorLabel('>='),
            'isNull' => getComparatorLabel('isNull'),
            'isNotNull' => getComparatorLabel('isNotNull'),
            'isEmpty' => getComparatorLabel('isEmpty'),
            'isNotEmpty' => getComparatorLabel('isNotEmpty'),

        ],
        'boolean' => ["0" => 'false', "1" => 'true'],
    ]
];
