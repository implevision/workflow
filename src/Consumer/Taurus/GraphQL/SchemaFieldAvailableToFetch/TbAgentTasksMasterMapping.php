<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

class TbAgentTasksMasterMapping
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
                    'policyTransaction' => [
                        'premiumChange' => null,
                        'policyFees' => null,
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction',
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
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.TbPolicy.policyNumber',
            ],
            
            'PolicyNumberWithoutPrefix' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'policy' => [
                            'policyNumber' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.policy.policyNumber',
                'parseResultCallback' => 'parsePolicyNumberWithoutPrefix',
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
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.tbAccountMaster.TbPersoninfo.fullName',
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
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.tbAccountMaster.TbPersoninfo.personUniqueId',
            ],
            'PotentialDiscountLostIndicator' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'id' => null,
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.id',
                'parseResultCallback' => 'parsePotentialDiscountLostIndicator',
            ],
            'PremiumCapDiscountAmount' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'id' => null,
                    ],
                ],
                'jqFilter' => '.policyAgentTaskQuery.agentTask.policyTransaction.id',
                'parseResultCallback' => 'parsePremiumCapDiscountAmount',
            ],
            'WyoAgencyAgentCode' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'TbPersoninfo' => [
                            'additionalInfo' => [
                                'wyoAgencyAgentCode' => null,
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
}