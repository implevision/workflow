<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

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
                    'policyId' => null,
                ],
                'jqFilter' => '.agentTask.policyId',
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
                'jqFilter' => '.agentTask.completeStatus',
            ],
            'CompleteDate' => [
                'GraphQLschemaToReplace' => [
                    'completeDate' => null,
                ],
                'jqFilter' => '.agentTask.completeDate',
                'parseResultCallback' => 'formatDate',
            ],
            'MetaData' => [
                'GraphQLschemaToReplace' => [
                    'metadata' => null,
                ],
                'jqFilter' => '.agentTask.metadata',
            ],
            'CreatedBy' => [
                'GraphQLschemaToReplace' => [
                    'createdBy' => null,
                ],
                'jqFilter' => '.agentTask.createdBy',
            ],
            'CreatedAt' => [
                'GraphQLschemaToReplace' => [
                    'createdAt' => null,
                ],
                'jqFilter' => '.agentTask.createdAt',
                'parseResultCallback' => 'formatDate',
            ],
            'UpdatedBy' => [
                'GraphQLschemaToReplace' => [
                    'updatedBy' => null,
                ],
                'jqFilter' => '.agentTask.updatedBy',
            ],
            'UpdatedAt' => [
                'GraphQLschemaToReplace' => [
                    'updatedAt' => null,
                ],
                'jqFilter' => '.agentTask.updatedAt',
                'parseResultCallback' => 'formatDate',
            ],
            'AssignedAgentEmail' => [
                'GraphQLschemaToReplace' => [
                    'agent' => [
                        'emailInfo' => [
                            'email' => null,
                            'isDefault' => null,
                        ],
                    ],
                ],
                'jqFilter' => '[.agentTask.agent.emailInfo[0] | select(.isDefault == "Y")]',
                'parseResultCallback' => 'parseAssignedAgentEmail',
            ],
            'Title' => [
                'GraphQLschemaToReplace' => [
                    'taskMapping' => [
                        'title' => null,
                    ],
                ],
                'jqFilter' => '[.agentTask.taskMapping.title]',
            ],
            'isEnabledForWorkflow' => [
                'GraphQLschemaToReplace' => [
                    'taskMapping' => [
                        'task' => [
                            'metadata' => null,
                        ],
                    ],
                ],
                'jqFilter' => '[.agentTask.taskMapping[].task.metadata]',
                'parseResultCallback' => 'parseIsEnabledForWorkflow',
            ],
            'Type' => [
                'GraphQLschemaToReplace' => [
                    'taskMapping' => [
                        'task' => [
                            'metadata' => null,
                        ],
                    ],
                ],
                'jqFilter' => '[.agentTask.taskMapping[].task.metadata]',
                'parseResultCallback' => 'parseTaskType',
            ],
            'SubType' => [
                'GraphQLschemaToReplace' => [
                    'taskMapping' => [
                        'task' => [
                            'metadata' => null,
                        ],
                    ],
                ],
                'jqFilter' => '[.agentTask.taskMapping[].task.metadata]',
                'parseResultCallback' => 'parseTaskSubType',
            ],
            'Reason' => [
                'GraphQLschemaToReplace' => [
                    'taskMapping' => [
                        'task' => [
                            'metadata' => null,
                        ],
                    ],
                ],
                'jqFilter' => '[.agentTask.taskMapping[].task.metadata]',
                'parseResultCallback' => 'parseTaskReason',
            ],
            'Task' => [
                'GraphQLschemaToReplace' => [
                    'taskMapping' => [
                        'task' => [
                            'metadata' => null,
                        ],
                    ],
                ],
                'jqFilter' => '[.agentTask.taskMapping[].task.metadata]',
                'parseResultCallback' => 'parseTaskDetails',
            ],
            'DocumentName' => [
                'GraphQLschemaToReplace' => [
                    'taskMapping' => [
                        'task' => [
                            'metadata' => null,
                        ],
                    ],
                ],
                'jqFilter' => '[.agentTask.taskMapping[].task.metadata]',
                'parseResultCallback' => 'parseTaskDocumentName',
            ],
            'SourceSystem' => [
                'GraphQLschemaToReplace' => [
                    'taskMapping' => [
                        'task' => [
                            'metadata' => null,
                        ],
                    ],
                ],
                'jqFilter' => '[.agentTask.taskMapping[].task.metadata]',
                'parseResultCallback' => 'parseSourceSystem',
            ],
            'PremiumDue' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'premiumChange' => null,
                        'policyFees' => null,
                    ],
                ],
                'jqFilter' => '.agentTask.policyTransaction',
                'parseResultCallback' => 'parsePremiumDue',
            ],
            'PolicyNumber' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'TbPolicy' => [
                            'policyNumber' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.agentTask.policyTransaction.TbPolicy.policyNumber',
            ],
            'AgencyName' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'tbAccountMaster' => [
                            'TbPersoninfo' => [
                                'fullName' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.agentTask.policyTransaction.tbAccountMaster.TbPersoninfo.fullName',
            ],
            'AgencyCode' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'tbAccountMaster' => [
                            'TbPersoninfo' => [
                                'personUniqueId' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.agentTask.policyTransaction.tbAccountMaster.TbPersoninfo.personUniqueId',
            ],

        ];

        return $fieldMapping;
    }

    public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }

    public function parseAssignedAgentEmail($emailArr)
    {
        return is_array($emailArr) && count($emailArr) ? (last($emailArr)['email'] ?? null) : null;
    }

    public function parsePremiumDue($premiumChangeAndFeesArr)
    {
        $premiumDue = 0;
        if (is_array($premiumChangeAndFeesArr)) {
            $premiumChange = $premiumChangeAndFeesArr['premiumChange'] ?? 0;
            $policyFees = $premiumChangeAndFeesArr['policyFees'] ?? 0;
            $premiumDue = $premiumChange + $policyFees;
        }

        return $premiumDue;
    }

    public function parseMetadata($metadata, $key)
    {
        if (! $metadata) {
            return null;
        }

        if (is_array($metadata)) {
            $metadataArr = [];
            foreach ($metadata as $metadataItem) {
                $metadataArr[] = $this->parseMetadata($metadataItem, $key);
            }

            return $metadataArr;
        }

        $metadataArr = json_decode($metadata, true);

        return $metadataArr[$key] ?? null;
    }

    public function parseTaskType($metadata)
    {
        return $this->parseMetadata($metadata, 'type');
    }

    public function parseTaskSubType($metadata)
    {
        return $this->parseMetadata($metadata, 'subType');
    }

    public function parseTaskReason($metadata)
    {
        return $this->parseMetadata($metadata, 'reason');
    }

    public function parseTaskDocumentName($metadata)
    {
        return $this->parseMetadata($metadata, 'documentName');
    }

    public function parseIsEnabledForWorkflow($metadata)
    {
        return $this->parseMetadata($metadata, 'isEnabledForWorkflow');
    }

    public function parseSourceSystem($metadata)
    {
        return $this->parseMetadata($metadata, 'sourceSystem');
    }

    public function parseTaskDetails($metadata)
    {
        return $this->parseMetadata($metadata, 'task');
    }
}
