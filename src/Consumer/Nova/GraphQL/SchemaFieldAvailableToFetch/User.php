<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

class User extends AbstractSchema
{
    protected $fieldMapping = [];

    protected $queryName = 'user';

    protected $queryPath;

    public function __construct()
    {
        $this->queryPath = '.'.$this->queryName;
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
        return [
            'Email' => [
                'GraphQLschemaToReplace' => ['email' => null],
                'jqFilter' => "{$this->queryPath}.email",
            ],
            'FirstName' => [
                'GraphQLschemaToReplace' => ['firstName' => null],
                'jqFilter' => "{$this->queryPath}.firstName",
            ],
            'LastName' => [
                'GraphQLschemaToReplace' => ['lastName' => null],
                'jqFilter' => "{$this->queryPath}.lastName",
            ],
            'FullName' => [
                'GraphQLschemaToReplace' => ['fullName' => null],
                'jqFilter' => "{$this->queryPath}.fullName",
            ],
            'UserLevel' => [
                'GraphQLschemaToReplace' => ['level' => null],
                'jqFilter' => "{$this->queryPath}.level",
            ],
            'Status' => [
                'GraphQLschemaToReplace' => ['status' => null],
                'jqFilter' => "{$this->queryPath}.status",
            ],
            'Username' => [
                'GraphQLschemaToReplace' => ['username' => null],
                'jqFilter' => "{$this->queryPath}.username",
            ],
            'CompanyLogo' => [
                'GraphQLschemaToReplace' => ['companyLogo' => null],
                'jqFilter' => "{$this->queryPath}.companyLogo",
            ],
        ];
    }
}
