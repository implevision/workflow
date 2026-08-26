<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

class User extends AbstractSchema
{
    protected $fieldMapping = [];

    protected $queryName = 'user';

    public function __construct()
    {
        $this->fieldMapping = $this->initializeFieldMapping();
    }

    public function getFieldMapping(): array
    {
        return $this->fieldMapping;
    }

    public function getQueryName(): string
    {
        return $this->queryName;
    }

    private function initializeFieldMapping(): array
    {
        $queryPath = '.'.$this->queryName;

        return [
            'Email' => [
                'GraphQLschemaToReplace' => ['email' => null],
                'jqFilter' => "{$queryPath}.email",
            ],
            'FirstName' => [
                'GraphQLschemaToReplace' => ['firstName' => null],
                'jqFilter' => "{$queryPath}.firstName",
            ],
            'LastName' => [
                'GraphQLschemaToReplace' => ['lastName' => null],
                'jqFilter' => "{$queryPath}.lastName",
            ],
            'FullName' => [
                'GraphQLschemaToReplace' => ['fullName' => null],
                'jqFilter' => "{$queryPath}.fullName",
            ],
            'UserLevel' => [
                'GraphQLschemaToReplace' => ['level' => null],
                'jqFilter' => "{$queryPath}.level",
            ],
            'Status' => [
                'GraphQLschemaToReplace' => ['status' => null],
                'jqFilter' => "{$queryPath}.status",
            ],
            'Username' => [
                'GraphQLschemaToReplace' => ['username' => null],
                'jqFilter' => "{$queryPath}.username",
            ],
            'CompanyLogo' => [
                'GraphQLschemaToReplace' => ['companyLogo' => null],
                'jqFilter' => "{$queryPath}.companyLogo",
            ],
        ];
    }
}
