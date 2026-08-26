<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

class TbUser extends AbstractSchema
{
    /**
     * @var array
     *
     * This property holds the mapping of fields that are available to fetch.
     * It is an associative array where keys represent PLACEHOLDER and values
     * represent the corresponding data or configuration for those fields.
     */
    protected $fieldMapping = [];

    /**
     * @var string|null The name of the query associated with this class.
     */
    protected $queryName;

    public function __construct()
    {
        $this->queryName = 'usersQuery';
        $this->fieldMapping = $this->initializeFieldMapping();
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the TbUser.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the TbUser.
     *
     * @return string The name of the GraphQL query for TbUser.
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Initializes the field mapping with GraphQL schema for the TbUser class.
     *
     * KEYS are PLACEHOLDER for the GraphQL schema to be replaced.
     *
     * @return array
     */
    private function initializeFieldMapping()
    {
        $queryPath = '.'.$this->queryName;

        $fieldMapping = [
            'UserId' => [
                'GraphQLschemaToReplace' => [
                    'id' => null,
                ],
                'jqFilter' => "{$queryPath}.id",
            ],
            'Username' => [
                'GraphQLschemaToReplace' => [
                    'username' => null,
                ],
                'jqFilter' => "{$queryPath}.username",
            ],
            'UserFirstName' => [
                'GraphQLschemaToReplace' => [
                    'firstName' => null,
                ],
                'jqFilter' => "{$queryPath}.firstName",
            ],
            'UserLastName' => [
                'GraphQLschemaToReplace' => [
                    'lastName' => null,
                ],
                'jqFilter' => "{$queryPath}.lastName",
            ],
            'Email' => [
                'GraphQLschemaToReplace' => [
                    'email' => null,
                ],
                'jqFilter' => "{$queryPath}.email",
            ],
            'UserFullName' => [
                'GraphQLschemaToReplace' => [
                    'screenName' => null,
                ],
                'jqFilter' => "{$queryPath}.screenName",
            ],
        ];

        $fieldMapping['LoginURL'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getLoginUrl',
        ];

        $fieldMapping['DashboardURL'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getDashboard',
        ];

        $fieldMapping['OutsideDocumentListURL'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getOutsideDocumentList',
        ];

        return $this->wrapFieldMappingSchemaUnderData($fieldMapping);
    }

    public function getLoginUrl(): string
    {
        return Helper::createPortalURL('AgentPortal').'/login';
    }

    public function getDashboard(): string
    {
        return Helper::createPortalURL('AgentPortal').'/dashboard';
    }

    public function getOutsideDocumentList(): string
    {
        // TODO: implement outside document list generation logic
        return '';
    }
}
