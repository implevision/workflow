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
        $this->fieldMapping = $this->initializeFieldMapping();
        $this->queryName = 'user';
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
        $fieldMapping = [

            'UserId' => [
                'GraphQLschemaToReplace' => [
                    'id' => null,
                ],
                'jqFilter' => '.user.id',
            ],

            'UserScreenName' => [
                'GraphQLschemaToReplace' => [
                    'screenName' => null,
                ],
                'jqFilter' => '.user.screenName',
            ],

            'Username' => [
                'GraphQLschemaToReplace' => [
                    'username' => null,
                ],
                'jqFilter' => '.user.username',
            ],

            'UserFirstName' => [
                'GraphQLschemaToReplace' => [
                    'firstName' => null,
                ],
                'jqFilter' => '.user.firstName',
            ],

            'UserLastName' => [
                'GraphQLschemaToReplace' => [
                    'lastName' => null,
                ],
                'jqFilter' => '.user.lastName',
            ],

            'Email' => [
                'GraphQLschemaToReplace' => [
                    'email' => null,
                ],
                'jqFilter' => '.user.email',
            ],

            'UserContactNumber' => [
                'GraphQLschemaToReplace' => [
                    'phoneInfo' => [
                        ['phoneNumber' => null, 'isDefault' => null],
                    ],
                ],
                'jqFilter' => '.user.phoneInfo[] | select(.isDefault == "Y" and .phoneNumber != "") | .phoneNumber',
            ],

        ];

        $fieldMapping['Password'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getTemporaryPassword',
        ];

        $fieldMapping['LoginURL'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getLoginUrl',
        ];

        $fieldMapping['Dashboard'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getDashboard',
        ];

        $fieldMapping['ContactUs'] = [
            'GraphQLschemaToReplace' => [
                'phoneInfo' => [
                    ['phoneNumber' => null, 'isDefault' => null],
                ],
            ],
            'jqFilter' => '.user.phoneInfo[] | select(.isDefault == "Y" and .phoneNumber != "") | .phoneNumber',
        ];

        $fieldMapping['OutsideDocumentList'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getOutsideDocumentList',
        ];

        return $fieldMapping;
    }

    public function getTemporaryPassword(): string
    {
        // TODO: implement temporary password generation logic
        return '';
    }

    public function getLoginUrl(): string
    {
        return Helper::createPortalURL('CorePortal') . '/login';
    }

    public function getDashboard(): string
    {
        return Helper::createPortalURL('CorePortal') . '/dashboard';
    }

    public function getOutsideDocumentList(): string
    {
        // TODO: implement outside document list generation logic
        return '';
    }
}
