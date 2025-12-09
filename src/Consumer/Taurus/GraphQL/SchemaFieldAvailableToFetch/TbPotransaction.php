<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

class TbPotransaction
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
        $this->queryName = 'policy';
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
            'PremiumDue' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'premiumChange' => null,
                        'policyFees' => null,
                    ],
                ],
                'jqFilter' => '.policy.policyTransaction',
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
                'jqFilter' => '.policy.policyTransaction.TbPolicy.policyNumber',
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
                'jqFilter' => '.policy.policyTransaction.tbAccountMaster.TbPersoninfo.fullName',
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
                'jqFilter' => '.policy.policyTransaction.tbAccountMaster.TbPersoninfo.personUniqueId',
            ],
            'PotentialDiscountLostIndicator' => [
                'GraphQLschemaToReplace' => [
                    'policyTransaction' => [
                        'id' => null,
                    ],
                ],
                'jqFilter' => '.policy.policyTransaction.id',
                'parseResultCallback' => 'parsePotentialDiscountLostIndicator',
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
                'jqFilter' => '.policy.policyTransaction.TbPersoninfo.additionalInfo.wyoAgencyAgentCode',
                'parseResultCallback' => 'parseWyoAgencyAgentCode',
            ],
        ];

        return $fieldMapping;
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

    public function parseWyoAgencyAgentCode($agentCode)
    {
        return (strlen($agentCode) === 7) ? substr_replace($agentCode, '', 4, 1) : $agentCode;
    }
}
