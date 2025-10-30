<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Carbon\Carbon;
use Taurus\Workflow\Consumer\Taurus\Helper;

class TbAgentTasksMaster
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
        $this->queryName = 'agentTask';
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the TbClaim.
     *
     * This method returns an associative array that maps the fields
     * of the TbClaim to their corresponding values or attributes.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the TbClaim.
     *
     * This method returns the name of the GraphQL query that can be used
     * to fetch data related to the TbClaim.
     *
     * @return string The name of the GraphQL query for TbClaim.
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Initializes the field mapping with GraphQL schema for the TbClaim class.
     *
     * This method sets up the mapping of fields that can be fetched
     * from the GraphQL schema. It is called during the initialization
     * phase of the class to ensure that all fields are properly mapped
     * before any operations are performed.
     *
     * KEYS are PLACEHOLDER for the GraphQL schema to be replaced.
     *
     * @return array
     */
    private function initializeFieldMapping()
    {
        $fieldMapping = [
            'TaskId' => [
                'GraphQLschemaToReplace' => [
                    'id' => null,
                ],
                'jqFilter' => '.agentTask.id',
            ],
            'TransactionId' => [
                'GraphQLschemaToReplace' => [
                    'transactionId' => null,
                ],
                'jqFilter' => '.agentTask.transactionId',
            ],
            'MasterId' => [
                'GraphQLschemaToReplace' => [
                    'policymasterId' => null,
                ],
                'jqFilter' => '.agentTask.policymasterId',
            ],
            'AgentId' => [
                'GraphQLschemaToReplace' => [
                    'agentId' => null,
                ],
                'jqFilter' => '.agentTask.agentId',
            ],
            'AgencyId' => [
                'GraphQLschemaToReplace' => [
                    'agencyId' => null,
                ],
                'jqFilter' => '.agentTask.agencyId',
            ],
            'Note' => [
                'GraphQLschemaToReplace' => [
                    'note' => null,
                ],
                'jqFilter' => '.agentTask.note',
            ],
            'IsActive' => [
                'GraphQLschemaToReplace' => [
                    'isActive' => null,
                ],
                'jqFilter' => '.agentTask.isActive',
            ],
            'IsDeleted' => [
                'GraphQLschemaToReplace' => [
                    'isDeleted' => null,
                ],
                'jqFilter' => '.agentTask.isDeleted',
            ],
            'CompleteStatus' => [
                'GraphQLschemaToReplace' => [
                    'completeStatus' => null,
                ],
                'jqFilter' => '.agentTask.completeStatus'
            ],
            'CompleteDate' => [
                'GraphQLschemaToReplace' => [
                    'completeDate' => null,
                ],
                'jqFilter' => '.agentTask.completeDate',
                'parseResultCallback' => 'formatDate'
            ],
            'MeataData' => [
                'GraphQLschemaToReplace' => [
                    'metadata' => null,
                ],
                'jqFilter' => '.agentTask.metadata'
            ],
            'CreatedBy' => [
                'GraphQLschemaToReplace' => [
                    'createdBy' => null,
                ],
                'jqFilter' => '.agentTask.createdBy'
            ],
            'CreatedAt' => [
                'GraphQLschemaToReplace' => [
                    'createdAt' => null,
                ],
                'jqFilter' => '.agentTask.createdAt',
                'parseResultCallback' => 'formatDate'
            ],
            'UpdatedBy' => [
                'GraphQLschemaToReplace' => [
                    'updatedBy' => null,
                ],
                'jqFilter' => '.agentTask.updatedBy'
            ],
            'UpdatedAt' => [
                'GraphQLschemaToReplace' => [
                    'updatedAt' => null,
                ],
                'jqFilter' => '.agentTask.updatedAt',
                'parseResultCallback' => 'formatDate',
            ],
        ];

       
        return $fieldMapping;
    }

    public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }
}