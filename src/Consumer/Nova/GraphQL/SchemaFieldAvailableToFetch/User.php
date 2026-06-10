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
        return [
            'Email' => [
                'GraphQLschemaToReplace' => ['email' => null],
                'jqFilter' => '.user.email',
            ],
            'FirstName' => [
                'GraphQLschemaToReplace' => ['firstName' => null],
                'jqFilter' => '.user.firstName',
            ],
            'LastName' => [
                'GraphQLschemaToReplace' => ['lastName' => null],
                'jqFilter' => '.user.lastName',
            ],
            'FullName' => [
                'GraphQLschemaToReplace' => ['fullName' => null],
                'jqFilter' => '.user.fullName',
            ],
            'UserLevel' => [
                'GraphQLschemaToReplace' => ['level' => null],
                'jqFilter' => '.user.level',
            ],
            'Status' => [
                'GraphQLschemaToReplace' => ['status' => null],
                'jqFilter' => '.user.status',
            ],
            'Username' => [
                'GraphQLschemaToReplace' => ['username' => null],
                'jqFilter' => '.user.username',
            ],
            'CompanyLogo' => [
                'GraphQLschemaToReplace' => ['companyLogo' => null],
                'jqFilter' => '.user.companyLogo',
            ],
        ];
    }
}
