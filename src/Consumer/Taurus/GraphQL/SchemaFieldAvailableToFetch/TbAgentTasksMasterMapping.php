<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Illuminate\Support\Facades\DB;
use Taurus\Workflow\Consumer\Taurus\Helper;

class TbAgentTasksMasterMapping extends AbstractSchema
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
        $this->queryName = 'policyAgentTaskQuery';
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
        $addressStructure = [
            'addressTypeCode' => null,
            'houseNo' => null,
            'streetName' => null,
            'addressLine1' => null,
            'addressLine2' => null,
            'addressLine3' => null,
            'addressLine4' => null,
            'postalCode' => null,
            'postalCodeSuffix' => null,
            'tbCity' => [
                'name' => null,
            ],
            'tbState' => [
                'name' => null,
            ],
            'tbCountry' => [
                'name' => null,
            ],
            'isDefaultAddress' => null,
        ];

        $fieldMapping = [
            'TaskId' => [
                'GraphQLschemaToReplace' => [
                    'id' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.id',
            ],
            'TransactionId' => [
                'GraphQLschemaToReplace' => [
                    'transactionId' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.transactionId',
            ],
            'MasterId' => [
                'GraphQLschemaToReplace' => [
                    'policyId' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyId',
            ],
            'AgentId' => [
                'GraphQLschemaToReplace' => [
                    'agentId' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.agentId',
            ],
            'AgencyId' => [
                'GraphQLschemaToReplace' => [
                    'agencyId' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.agencyId',
            ],
            'Note' => [
                'GraphQLschemaToReplace' => [
                    'note' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.note',
            ],
            'IsActive' => [
                'GraphQLschemaToReplace' => [
                    'isActive' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.isActive',
            ],
            'IsDeleted' => [
                'GraphQLschemaToReplace' => [
                    'isDeleted' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.isDeleted',
            ],
            'CompleteStatus' => [
                'GraphQLschemaToReplace' => [
                    'completeStatus' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.completeStatus',
            ],
            'CompleteDate' => [
                'GraphQLschemaToReplace' => [
                    'completeDate' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.completeDate',
                'parseResultCallback' => 'formatDate',
            ],
            'MetaData' => [
                'GraphQLschemaToReplace' => [
                    'metadata' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.metadata',
            ],
            'CreatedBy' => [
                'GraphQLschemaToReplace' => [
                    'createdBy' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.createdBy',
            ],
            'CreatedAt' => [
                'GraphQLschemaToReplace' => [
                    'createdAt' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.createdAt',
                'parseResultCallback' => 'formatDateToGMT',
            ],
            'UpdatedBy' => [
                'GraphQLschemaToReplace' => [
                    'updatedBy' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.updatedBy',
            ],
            'UpdatedAt' => [
                'GraphQLschemaToReplace' => [
                    'updatedAt' => null,
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.updatedAt',
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
                'jqFilter' => '[.policyAgentTaskQuery.agentTask.agent.emailInfo[0] | select(.isDefault == "Y")]',
                'parseResultCallback' => 'parseAssignedAgentEmail',
            ],

            'Title' => [
                'GraphQLschemaToReplace' => [
                    'title' => null,
                ],
                'jqFilter' => '[.policyAgentTaskQuery.title]',
            ],
            'isEnabledForWorkflow' => [
                'GraphQLschemaToReplace' => [
                    'task' => [
                        'metadata' => null,
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.task.metadata]',
                'parseResultCallback' => 'parseIsEnabledForWorkflow',
            ],
            'Type' => [
                'GraphQLschemaToReplace' => [
                    'task' => [
                        'metadata' => null,
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.task.metadata]',
                'parseResultCallback' => 'parseTaskType',
            ],
            'SubType' => [
                'GraphQLschemaToReplace' => [
                    'task' => [
                        'metadata' => null,
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.task.metadata]',
                'parseResultCallback' => 'parseTaskSubType',
            ],
            'Reason' => [
                'GraphQLschemaToReplace' => [
                    'task' => [
                        'metadata' => null,
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.task.metadata]',
                'parseResultCallback' => 'parseTaskReason',
            ],
            'ReasonCode' => [
                'GraphQLschemaToReplace' => [
                    'task' => [
                        'metadata' => null,
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.task.metadata]',
                'parseResultCallback' => 'parseTaskReasonCode',
            ],
            'Task' => [
                'GraphQLschemaToReplace' => [
                    'task' => [
                        'metadata' => null,
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.task.metadata]',
                'parseResultCallback' => 'parseTaskDetails',
            ],
            'DocumentName' => [
                'GraphQLschemaToReplace' => [
                    'task' => [
                        'metadata' => null,
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.task.metadata]',
                'parseResultCallback' => 'parseTaskDocumentName',
            ],
            'SourceSystem' => [
                'GraphQLschemaToReplace' => [
                    'task' => [
                        'metadata' => null,
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.task.metadata]',
                'parseResultCallback' => 'parseSourceSystem',
            ],
            'DueDate' => [
                'GraphQLschemaToReplace' => [
                    'task' => [
                        'metadata' => null,
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.task.metadata]',
                'parseResultCallback' => 'parseDueDate',
            ],
            'PremiumDue' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'premiumChange' => null,
                            'policyFees' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction',
                'parseResultCallback' => 'parsePremiumDue',
            ],

            'PolicyNumber' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'policyNumber' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.policyNumber',
            ],

            'PolicyNumberWithoutPrefix' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'policyNumber' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.policyNumber',
                'parseResultCallback' => 'parsePolicyNumberWithoutPrefix',
            ],

            'AgencyName' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'tbAccountMaster' => [
                                'TbPersoninfo' => [
                                    'fullName' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.tbAccountMaster.TbPersoninfo.fullName',
            ],
            'AgencyCode' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'tbAccountMaster' => [
                                'TbPersoninfo' => [
                                    'personUniqueId' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.tbAccountMaster.TbPersoninfo.personUniqueId',
            ],
            'PotentialDiscountLostIndicator' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'id' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.id',
                'parseResultCallback' => 'parsePotentialDiscountLostIndicator',
            ],
            'PremiumCapDiscountAmount' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'id' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.id',
                'parseResultCallback' => 'parsePremiumCapDiscountAmount',
            ],
            'WyoAgencyAgentCode' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'TbPersoninfo' => [
                                'additionalInfo' => [
                                    'wyoAgencyAgentCode' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.TbPersoninfo.additionalInfo.wyoAgencyAgentCode',
                'parseResultCallback' => 'parseWyoAgencyAgentCode',
            ],
            'UUID' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'getUUID',
            ],

            'TodaysDate' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'getTodaysDate',
            ],

            'NameAsOnTitle' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'insuredPersonInfo' => [
                                    'fullName' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.insuredPersonInfo.fullName',
            ],

            'PolicyExpirationDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'transactionEffectiveToDate' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.transactionEffectiveToDate',
                'parseResultCallback' => 'formatDate',
            ],

            'TermStartDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policyTermMaster' => [
                                'termStartDate' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policyTermMaster.termStartDate',
                'parseResultCallback' => 'formatDate',
            ],

            'TermEndDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policyTermMaster' => [
                                'termEndDate' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policyTermMaster.termEndDate',
                'parseResultCallback' => 'formatDate',
            ],

            'ProductName' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'product' => [
                                    'productName' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.product.productName',
            ],

            'TransactionType' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policyRiskTransactionType' => [
                                'transactionTypeScreenName' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policyRiskTransactionType.transactionTypeScreenName',
            ],

            'TransactionSubType' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policyRiskTransactionType' => [
                                'policyRiskTransactionTypeCode' => null,
                            ],
                            'policyRiskTransactionSubType' => [
                                'transactionSubTypeScreenName' => null,
                            ],
                            'floodTransactionSubType' => [
                                'reasonCode' => null,
                            ],
                            'policy' => [
                                'product' => [
                                    'productCode' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask',
                'parseResultCallback' => 'transactionSubTypeScreenNameResolver',
            ],

            'WaitingPeriod' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [

                            'riskAdditionalFloodInfo' => [
                                'policyWaitingPeriod' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.policyWaitingPeriod',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],

            'RenewalIndicator' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'renewalTypeCode' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.renewalTypeCode',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],

            'BillTo' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'accountMaster' => [
                                    'billToType' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.accountMaster.billToType',
                'parseResultCallback' => 'parseBillTo',
            ],
            'UnderWriterApplicationStatus' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'policyApplicationMaster' => [
                                    'underwriterApplicationStatusTypeCode' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.policyApplicationMaster.underwriterApplicationStatusTypeCode',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],

            'TransactionEffectiveDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'transactionEffectiveFromDate' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.transactionEffectiveFromDate',
                'parseResultCallback' => 'formatDate',
            ],

            'TotalPremium' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'totalPremium' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.totalPremium',
                'parseResultCallback' => 'formatCurrency',
            ],

            'ReplacementCost' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'replacementCost' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.replacementCost',
                'parseResultCallback' => 'formatCurrency',
            ],

            'AccountingDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'accountingDate' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.accountingDate',
                'parseResultCallback' => 'formatDate',
            ],

            'EffectiveDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policyTermMaster' => [
                                'termStartDate' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policyTermMaster.termStartDate',
                'parseResultCallback' => 'formatDate',
            ],

            'InsuredPropertyAddress' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'insuredPersonInfo' => [
                                    'TbPersonaddress' => $addressStructure,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.insuredPersonInfo.TbPersonaddress[] | select(.isDefaultAddress == "Y" and .addressTypeCode == "Location")',
                'parseResultCallback' => 'parsePropertyAddress',
            ],

            'InsuredEmail' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'insuredPersonInfo' => [
                                    'emailInfo' => [
                                        'email' => null,
                                        'isDefault' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.agentTask.policyTransaction.policy.insuredPersonInfo.emailInfo[0] | select(.isDefault == "Y")]',
                'parseResultCallback' => 'parseInsuredPersonEmail',
            ],

            'InsuredPhoneNumber' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'insuredPersonInfo' => [
                                    'phoneInfo' => [
                                        'phoneNumber' => null,
                                        'isDefault' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '[.policyAgentTaskQuery.agentTask.policyTransaction.policy.insuredPersonInfo.phoneInfo[0] | select(.isDefault == "Y")]',
                'parseResultCallback' => 'parseInsuredPersonPhone',
            ],

            'IsPolicyholderOwnerOrTenant' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'isPolicyholderOwnerOrTenant' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.isPolicyholderOwnerOrTenant',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],

            'IsPolicyRentalProperty' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'isRentalProperty' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.isRentalProperty',
                'parseResultCallback' => 'parseYesNoDisplayName',
            ],

            'IsPolicyholderCondominiumAssociation' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'condoOwnership' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.condoOwnership',
                'parseResultCallback' => 'parseYesNoDisplayName',
            ],

            'CommunityNumber' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'communityNumber' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.communityNumber',
            ],

            'PanelNumber' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'panelNumber' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.panelNumber',
            ],
            'MapSuffix' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'mapSuffix' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.mapSuffix',
            ],
            'FloodZone' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'floodZone' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.floodZone',
            ],
            'CountyName' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'countyName' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.countyName',
            ],
            'InitialFirmDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'initialFirmDate' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.initialFirmDate',
                'parseResultCallback' => 'formatDate',
            ],
            'CurrentFirmDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'currentFirmDate' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.currentFirmDate',
                'parseResultCallback' => 'formatDate',
            ],
            'CurrentBaseFloodElevation' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'baseElevation' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.baseElevation',
                'parseResultCallback' => 'formatNumber',
            ],
            'IsBuildingLocatedInCoastalBarrierResourcesSystemArea' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'isCBRSorOPA' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.isCBRSorOPA',
                'parseResultCallback' => 'parseYesNoDisplayName',
            ],
            'ConstructionDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'dateOfConstruction' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.dateOfConstruction',
                'parseResultCallback' => 'formatDate',
            ],
            'OccupancyType' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'occupancyType' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.occupancyType',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'BuildingDescription' => [
                'agentTask' => [
                    'policyTransaction' => [
                        'GraphQLschemaToReplace' => [
                            'riskAdditionalFloodInfo' => [
                                'buildingUse' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.buildingUse',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'FoundationType' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'foundationType' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.foundationType',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'TotalSquareFootage' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'totalSquareFootage' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.totalSquareFootage',
                'parseResultCallback' => 'formatNumber',
            ],
            'NumberOfFloors' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'numberOfFloors' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.numberOfFloors',
                'parseResultCallback' => 'formatNumber',
            ],

            'ECCertificateSignatureDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'elevationCertificate' => [
                                'certificateDate' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.elevationCertificate.certificateDate',
                'parseResultCallback' => 'formatDate',
            ],
            'DiagramNumber' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'elevationCertificate' => [
                                'buildingDiagramNoCode' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.elevationCertificate.buildingDiagramNoCode',
            ],
            'TopOfBottomFloorInFeet' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'elevationCertificate' => [
                                'topOfBottomFloor' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.elevationCertificate.topOfBottomFloor',
                'parseResultCallback' => 'formatNumber',
            ],
            'TopOfNextHigherFloorInFeet' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'elevationCertificate' => [
                                'topOfNextHigherFloor' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.elevationCertificate.topOfNextHigherFloor',
                'parseResultCallback' => 'formatNumber',
            ],
            'LowestAdjacentGrade' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'elevationCertificate' => [
                                'lowestAdjacentGrade' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.elevationCertificate.lowestAdjacentGrade',
                'parseResultCallback' => 'formatNumber',
            ],
            'LoanClosingDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'riskAdditionalFloodInfo' => [
                                'floodLoanClosingDate' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.riskAdditionalFloodInfo.floodLoanClosingDate',
                'parseResultCallback' => 'formatDate',
            ],

            'PaymentTransactionNumber' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'id' => null,
                            'policy' => [
                                'product' => [
                                    'productCode' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '{metadata: .policyAgentTaskQuery?.agentTask?.policyTransaction?.policy?.policyAccountingPaymentLog?[-1]?.metadata?, id: .policyAgentTaskQuery?.agentTask?.policyTransaction?.policy?.id?, productCode: .policyAgentTaskQuery?.agentTask?.policyTransaction?.policy?.product?.productCode?}',
                'parseResultCallback' => 'parsePaymentTransactionNumber',
            ],
            'PaymentReceivedDate' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'id' => null,
                            'policy' => [
                                'product' => [
                                    'productCode' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '{metadata: .policyAgentTaskQuery?.agentTask?.policyTransaction?.policy?.policyAccountingPaymentLog?[-1]?.metadata?, id: .policyAgentTaskQuery?.agentTask?.policyTransaction?.policy?.id?, productCode: .policyAgentTaskQuery?.agentTask?.policyTransaction?.policy?.product?.productCode?}',
                'parseResultCallback' => 'parsePaymentReceivedDate',
            ],

            'AgentName' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'policy' => [
                                'agentInfo' => [
                                    'fullName' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.agentInfo.fullName',
            ],

            'WYOCompanyName' => [
                'GraphQLschemaToReplace' => [
                    'agentTask' => [
                        'policyTransaction' => [
                            'tbAccountMaster' => [
                                'TbPersoninfo' => [
                                    'brandedCompany' => [
                                        'company' => [
                                            'companyName' => null,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.tbAccountMaster.TbPersoninfo.brandedCompany[]',
                'parseResultCallback' => 'parseCompanyName',
            ],
        ];

        $fieldMapping['InsuredMailingAddress'] = [
            'GraphQLschemaToReplace' => $fieldMapping['InsuredPropertyAddress']['GraphQLschemaToReplace'],
            'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.insuredPersonInfo.TbPersonaddress[] | select(.addressTypeCode == "Mailing")',
            'parseResultCallback' => 'parseMailingAddress',
        ];

        $fieldMapping['PrimaryMortgageeName'] = [
            'GraphQLschemaToReplace' => [
                'agentTask' => [
                    'policyTransaction' => [
                        'mortgageeInfo' => [
                            'mortgageeType' => null,
                            'mortgageePersonInfo' => [
                                'fullName' => null,
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.mortgageeInfo[] | select(.mortgageeType == "PRIMARY")',
            'parseResultCallback' => 'parsePrimaryMortgageeName',
        ];

        $fieldMapping['PrimaryMortgageeLoanNumber'] = [
            'GraphQLschemaToReplace' => [
                'agentTask' => [
                    'policyTransaction' => [
                        'mortgageeInfo' => [
                            'mortgageeType' => null,
                            'loanNumber' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.mortgageeInfo[] | select(.mortgageeType == "PRIMARY")',
            'parseResultCallback' => 'parseLoanNumber',
        ];

        $fieldMapping['PrimaryMortgageeAddress'] = [
            'GraphQLschemaToReplace' => [
                'agentTask' => [
                    'policyTransaction' => [
                        'mortgageeInfo' => [
                            'mortgageeType' => null,
                            'mortgageeAddress' => $addressStructure,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.mortgageeInfo[] | select(.mortgageeType == "PRIMARY")',
            'parseResultCallback' => 'parsePrimaryMortgageeAddress',
        ];

        $fieldMapping['AdditionalInsuredName'] = [
            'GraphQLschemaToReplace' => [
                'agentTask' => [
                    'policyTransaction' => [
                        'additionalInterestInfo' => [
                            'partyInterestCode' => null,
                            'additionalPersonInfo' => [
                                'fullName' => null,
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.additionalInterestInfo[] | select(.partyInterestCode == "ADDITIONALINSURED")',
            'parseResultCallback' => 'parseAdditionalInsuredName',
        ];

        $fieldMapping['InsuredPortal'] = [
            'GraphQLschemaToReplace' => [
                'agentTask' => [
                    'policyTransaction' => [
                        'tbAccountMaster' => [
                            'TbPersoninfo' => [
                                'brandedCompany' => [
                                    'company' => [
                                        'insuredPortal' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.policyAgentTaskQuery?.agentTask?.policyTransaction?.tbAccountMaster?.TbPersoninfo?.brandedCompany?[0]?.company?.insuredPortal?',
            'parseResultCallback' => 'getInsuredPortalUrl',
        ];

        $fieldMapping['AgentPortal'] = [
            'GraphQLschemaToReplace' => [
                'agentTask' => [
                    'policyTransaction' => [
                        'tbAccountMaster' => [
                            'TbPersoninfo' => [
                                'brandedCompany' => [
                                    'company' => [
                                        'insuredPortal' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.policyAgentTaskQuery?.agentTask?.policyTransaction?.tbAccountMaster?.TbPersoninfo?.brandedCompany?[0]?.company?.insuredPortal?',
            'parseResultCallback' => 'getAgentPortalUrl',
        ];

        return $fieldMapping;
    }

    public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }

    public function formatDateToGMT($dateToFormat)
    {
        return gmdate('Y-m-d-H.i.s.v', time());
    }

    public function parseAssignedAgentEmail($emailArr)
    {
        return is_array($emailArr) && count($emailArr)
            ? (last($emailArr)['email'] ?? null)
            : null;
    }

    public function parsePremiumDue($premiumChangeAndFeesArr)
    {
        $premiumDue = 0;
        if (is_array($premiumChangeAndFeesArr)) {
            $premiumChange = $premiumChangeAndFeesArr['premiumChange'] ?? 0;
            $policyFees = $premiumChangeAndFeesArr['policyFees'] ?? 0;
            $premiumDue = $premiumChange + $policyFees;
        }

        return number_format($premiumDue, 2);
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

    public function parseTaskReasonCode($metadata)
    {
        return $this->parseMetadata($metadata, 'reasonCode');
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

    public function parsePotentialDiscountLost($transactionId, $coverageCode)
    {
        // TODO: TMP fix. Need to covert to actual one
        $coverageData = \DB::select(
            'SELECT cvgt.n_CvgSegmentGrossPremium AS coverage_premium
                from tb_potransactions pot
                left join tb_policies pol on pol.n_PolicyNoId_PK = pot.n_PolicyMaster_FK
                left join tb_pocoveragetrans cvgt on cvgt.n_POTransactionFK = pot.n_potransaction_PK
                left join tb_pocoverageschedules cvgs on cvgs.n_POCoverageSchedule_PK = cvgt.n_POCoverageScheduleFK
                left join tb_pocoveragemasters cvgm on cvgm.n_POCoverageMaster_PK = cvgs.n_POCoverageMasterFk
                left join tb_cvgpccoverages cvgp on cvgp.n_PCCoverageID_PK = cvgm.n_PRCoverageFK
                left join tb_poriskmasters risk on risk.n_PORiskMaster_PK = cvgs.n_PORiskMasterFK
                where pot.n_potransaction_PK IN(:transactionId)
                AND cvgp.s_CoverageCode = :coverageCode',
            ['coverageCode' => $coverageCode, 'transactionId' => $transactionId]
        );

        return isset($coverageData['coverage_premium']) ? $coverageData['coverage_premium'] : 0;
    }

    public function parsePotentialDiscountLostIndicator($transactionId)
    {
        $coverageAmount = $this->parsePotentialDiscountLost($transactionId, 'ANNUALCAPDISC');

        return $coverageAmount > 0 ? true : false;
    }

    public function parsePremiumCapDiscountAmount($transactionId)
    {
        $coverageAmount = $this->parsePotentialDiscountLost($transactionId, 'ANNUALCAPDISC');

        return number_format($coverageAmount, 2, '.', '');
    }

    public function getUUID()
    {
        return (string) \Str::uuid();
    }

    public function parseDueDate($metadata)
    {
        $dueDateArr = $this->parseMetadata($metadata, 'dueDate');
        if (is_array($dueDateArr)) {
            foreach ($dueDateArr as $index => $dueDate) {
                if (str_starts_with($dueDate, '+')) {
                    $dueDateArr[$index] = date('Y-m-d', strtotime($dueDate, time()));
                }
            }
        } elseif (is_string($dueDateArr) && str_starts_with($dueDateArr, '+')) {
            $dueDateArr = date('Y-m-d', strtotime($dueDateArr, time()));
        }

        return $dueDateArr;
    }

    public function parseWyoAgencyAgentCode($agentCode)
    {
        return (strlen($agentCode) === 7)
            ? substr_replace($agentCode, '', 4, 1)
            : $agentCode;
    }

    public function parsePolicyNumberWithoutPrefix($policyNumber)
    {
        $policyNoInitials = DB::table('tb_products')
            ->pluck('s_PolicyNoInitial') // Fetch the column values
            ->toArray();

        $regex = '/^('.implode('|', $policyNoInitials).')/';

        return preg_replace($regex, '', $policyNumber);
    }

    public function getTodaysDate(): string
    {
        return Helper::getTodaysDate();
    }

    public function transactionSubTypeScreenNameResolver($policyData)
    {
        $policyTransaction = $policyData['policyTransaction'] ?? [];
        $productCode = $policyTransaction['policy']['product']['productCode'] ?? null;
        $isNfipProduct = Helper::isNfipProduct($productCode);

        if (
            $policyTransaction['policyRiskTransactionType']['policyRiskTransactionTypeCode'] === 'ENDORSE'
            &&
            $isNfipProduct
        ) {
            $reasonCode = $policyTransaction['floodTransactionSubType']['reasonCode'] ?? '';

            $reasonCodeArray = explode(',', $reasonCode);
            $displayNameArray = [];

            foreach ($reasonCodeArray as $trrpMapping) {
                $ddGroup = 'FLENDORSEMENTTRANSUBTYPE';
                $appCodeNameForDisplay = Helper::parseAppCodeNameToDisplayNameUsingDDGroup(
                    $ddGroup,
                    $trrpMapping,
                    's_TRRPMapping'
                );

                $displayNameArray[] = $appCodeNameForDisplay;
            }

            $reasonString = implode(', ', $displayNameArray);

            return $reasonString;
        } else {
            return $policyTransaction['policyRiskTransactionSubType']['transactionSubTypeScreenName'] ?? null;
        }
    }

    public function getInsuredPortalUrl($insuredPortal)
    {
        // Returns holding company website URL if insuredPortal is empty
        if (empty($insuredPortal)) {
            $holdingCompanyDetail = Helper::getHoldingCompanyDetail();

            return $holdingCompanyDetail['insured_portal'];
        }

        // Otherwise, return insuredPortal
        return $insuredPortal;
    }

    public function getAgentPortalUrl($insuredPortal)
    {
        if (empty($insuredPortal)) {
            $holdingCompanyDetail = Helper::getHoldingCompanyDetail();
            $insuredPortal = $holdingCompanyDetail['agent_portal'] ?? null;

            if (empty($insuredPortal)) {
                return Helper::createPortalURL('AgentPortal');
            }
        }

        return str_replace('mypolicy', 'agent', $insuredPortal);
    }

    public function parseYesNoDisplayName($value)
    {
        return Helper::parseYesNoDisplayName($value);
    }

    public function parseAppCodeNameToDisplayName($appCodeName)
    {
        return Helper::parseAppCodeNameToDisplayName($appCodeName);
    }

    public function formatCurrency($amount)
    {
        return Helper::formatCurrency($amount);
    }

    public function parseBillTo($appCodeName)
    {
        $ddGroup = 'BILLTOFLOOD'; // TODO: Confirm whether 'BILLTO' should be used for non-flood products
        $label = Helper::parseAppCodeNameToDisplayNameUsingDDGroup($ddGroup, $appCodeName);

        return $label;
    }

    public function parsePropertyAddress($addressArr)
    {
        return $this->parseAddress($addressArr);
    }

    public function parseInsuredPersonEmail($emailArr)
    {
        return is_array($emailArr) && count($emailArr) ? (last($emailArr)['email'] ?? null) : null;
    }

    public function parseInsuredPersonPhone($phoneArr)
    {
        $phone = is_array($phoneArr) && count($phoneArr) ? (last($phoneArr)['phoneNumber'] ?? null) : null;
        if ($phone) {
            $phone = Helper::formatPhone($phone);
        }

        return $phone;
    }

    public function formatNumber($number)
    {
        return Helper::formatNumber($number);
    }

    private function parseAddress($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $address = [
            'addressLine1' => ($addressArr['houseNo'] ?? '').' '.($addressArr['streetName'] ?? ($addressArr['addressLine1'] ?? '')),
            'city' => $addressArr['tbCity']['name'] ?? null,
            // 'county' => $addressArr['tbCounty']['name'] ?? null,
            'state' => $addressArr['tbState']['name'] ?? null,
            'postalCode' => $addressArr['postalCode'] ?? null,
        ];

        if (! empty($address['postalCode']) && ! empty($addressArr['postalCodeSuffix'])) {
            $address['postalCode'] .= ' - '.$addressArr['postalCodeSuffix'];
        }

        $address = array_filter(array_map('trim', $address), function ($item) {
            return ! empty($item);
        });

        return implode(', ', $address);
    }

    public function parseMailingAddress($addressArr)
    {
        return $this->parseAddress($addressArr);
    }

    public function parsePrimaryMortgageeAddress($mortgagee)
    {
        return $this->parseAddress($mortgagee['mortgageeAddress'] ?? []);
    }

    public function parseLoanNumber($mortgagee)
    {
        return $mortgagee['loanNumber'] ?? null;
    }

    public function parsePaymentReceivedDate($data)
    {
        if (! is_array($data)) {
            return null;
        }

        $metadata = $data['metadata'] ?? null;
        $id = $data['id'] ?? null;
        $productCode = $data['productCode'] ?? null;

        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        if (! is_array($metadata)) {
            return null;
        }

        if ($productCode === 'HiscoxFloodPlus') {
            $stripeResponse = $metadata['stripe_response'] ?? null;

            if (is_string($stripeResponse)) {
                $stripeResponse = json_decode($stripeResponse, true);
            }

            if (! is_array($stripeResponse)) {
                return null;
            }

            $stripeMetadata = $stripeResponse['metadata'] ?? null;

            if (is_array($stripeMetadata) && (string) ($stripeMetadata['transaction_id'] ?? '') === (string) $id) {
                return $this->formatDate($stripeResponse['created'] ?? null);
            }

            return null;
        }

        // Default: FLOOD / NFIP products
        $transactionDate = $metadata['completeOnlineCollectionWithDetails']['response']['completeOnlineCollectionWithDetailsResponse']['transaction_date'] ?? null;

        return $transactionDate ? $this->formatDate($transactionDate) : null;
    }

    public function parsePrimaryMortgageeName($mortgagee)
    {
        return $mortgagee['mortgageePersonInfo']['fullName'] ?? null;
    }

    public function parseAdditionalInsuredName($additionalInterest)
    {
        return $additionalInterest['additionalPersonInfo']['fullName'] ?? null;
    }

    public function parseCompanyName($brandedCompanyArr)
    {
        // Ensure we are working with an array and 'company' key exists and is an array
        if (is_array($brandedCompanyArr) && isset($brandedCompanyArr['company']) && is_array($brandedCompanyArr['company'])) {
            $companyName = $brandedCompanyArr['company']['companyName'] ?? null;
            if (! empty($companyName)) {
                return $companyName;
            }
        }

        // Fallback to holding company name if not found
        $holdingCompanyDetail = Helper::getHoldingCompanyDetail();

        return $holdingCompanyDetail['wyo'] ?? '';
    }

    public function parsePaymentTransactionNumber($data)
    {
        if (! is_array($data)) {
            return null;
        }

        $metadata = $data['metadata'] ?? null;
        $id = $data['id'] ?? null;
        $productCode = $data['productCode'] ?? null;

        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        if (! is_array($metadata)) {
            return null;
        }

        if ($productCode === 'HiscoxFloodPlus') {
            $stripeResponse = $metadata['stripe_response'] ?? null;

            if (is_string($stripeResponse)) {
                $stripeResponse = json_decode($stripeResponse, true);
            }

            if (! is_array($stripeResponse)) {
                return null;
            }

            $stripeMetadata = $stripeResponse['metadata'] ?? null;

            if (is_array($stripeMetadata) && (string) ($stripeMetadata['transaction_id'] ?? '') === (string) $id) {
                return $stripeResponse['id'] ?? null;
            }

            return null;
        }

        // Default: FLOOD / NFIP products
        return $metadata['completeOnlineCollectionWithDetails']['response']['completeOnlineCollectionWithDetailsResponse']['paygov_tracking_id'] ?? null;
    }
}
