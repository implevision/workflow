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
        $this->queryName = 'userQuery';
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
     * Signals that this class handles its own record extraction via
     * getRecordsFromResponse(), bypassing the jqFilter mechanism.
     * This is what enables bulk email — one record per user in the response.
     */
    public function hasCustomRecordExtraction(): bool
    {
        return true;
    }

    /**
     * Turns every user in the GraphQL response into its own payload record.
     * Each record becomes one recipient, so DispatchWorkflowService builds a
     * multi-recipient payload and SES::createRequest() routes it through
     * sendBulkEmail() automatically.
     */
    public function getRecordsFromResponse(array $response): array
    {
        $node = $response['userQuery'] ?? [];

        // Handle both shapes in one place:
        //   - paginated:  userQuery.data[]  (with paginatorInfo)
        //   - direct list: userQuery[]      (no data/paginatorInfo wrapper)
        // Empty/missing response falls through to [], so no records are built.
        $users = $node['data'] ?? (array_is_list($node) ? $node : []);

        $records = [];
        foreach ($users as $user) {
            if (empty($user['email'])) {
                continue;
            }

            $records[] = [
                'UserId' => $user['id'] ?? '',
                'Username' => $user['username'] ?? '',
                'UserFirstName' => $user['firstName'] ?? '',
                'UserLastName' => $user['lastName'] ?? '',
                'Email' => $user['email'] ?? '',
                'UserFullName' => $user['screenName'] ?? '',

                'LoginURL' => $this->getLoginUrl(),
                'DashboardURL' => $this->getDashboard(),
                'OutsideDocumentListURL' => $this->getOutsideDocumentList(),
            ];
        }

        return $records;
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
        $dataSchema = [
            'data' => [
                [
                    'id' => null,
                    'username' => null,
                    'firstName' => null,
                    'lastName' => null,
                    'email' => null,
                    'screenName' => null,
                ],
            ],
        ];

        $fieldMapping = [
            'UserId' => ['GraphQLschemaToReplace' => $dataSchema],
            'Username' => ['GraphQLschemaToReplace' => $dataSchema],
            'UserFirstName' => ['GraphQLschemaToReplace' => $dataSchema],
            'UserLastName' => ['GraphQLschemaToReplace' => $dataSchema],
            'Email' => ['GraphQLschemaToReplace' => $dataSchema],
            'UserFullName' => ['GraphQLschemaToReplace' => $dataSchema],
        ];

        $fieldMapping['LoginURL'] = [
            'GraphQLschemaToReplace' => $dataSchema,
            'jqFilter' => '',
            'parseResultCallback' => 'getLoginUrl',
        ];

        $fieldMapping['DashboardURL'] = [
            'GraphQLschemaToReplace' => $dataSchema,
            'jqFilter' => '',
            'parseResultCallback' => 'getDashboard',
        ];

        $fieldMapping['OutsideDocumentListURL'] = [
            'GraphQLschemaToReplace' => $dataSchema,
            'jqFilter' => '',
            'parseResultCallback' => 'getOutsideDocumentList',
        ];

        return $fieldMapping;
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
