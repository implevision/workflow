<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

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
        $this->queryName = 'policyQuery';
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
                    'premiumChange' => null,
                    'policyFees' => null,
                ],
                'jqFilter' => '.policyQuery',
                'parseResultCallback' => 'parsePremiumDue',
            ],
            'PolicyNumber' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'policyNumber' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.policyNumber',
            ],
            'AgencyName' => [
                'GraphQLschemaToReplace' => [
                    'tbAccountMaster' => [
                        'TbPersoninfo' => [
                            'fullName' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.tbAccountMaster.TbPersoninfo.fullName',
            ],
            'AgencyCode' => [
                'GraphQLschemaToReplace' => [
                    'tbAccountMaster' => [
                        'TbPersoninfo' => [
                            'personUniqueId' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.tbAccountMaster.TbPersoninfo.personUniqueId',
            ],
            'PotentialDiscountLostIndicator' => [
                'GraphQLschemaToReplace' => [
                    'id' => null,
                ],
                'jqFilter' => '.policyQuery.id',
                'parseResultCallback' => 'parsePotentialDiscountLostIndicator',
            ],
            'WyoAgencyAgentCode' => [
                'GraphQLschemaToReplace' => [
                    'TbPersoninfo' => [
                        'additionalInfo' => [
                            'wyoAgencyAgentCode' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.TbPersoninfo.additionalInfo.wyoAgencyAgentCode',
                'parseResultCallback' => 'parseWyoAgencyAgentCode',
            ],
            'AttachDecPage' => [
                'GraphQLschemaToReplace' => [
                    'docurl' => null,
                ],
                // This finds the correct DECLARATION document,
                // then extracts the first docInfo.docurl value.
                'jqFilter' => '
                [
                      .policyQuery.policy.docuploadinfo[]
                      | select(
                      .doctypes.docTypeCode == "DECLARATION"
                      and
                      (.docUploadDocInfoRel[].docUploadReference.tableMasters.tableName == "tb_potransactions")
                      )
                      | .docUploadDocInfoRel[]
                      | .docInfo[]
                      | .docPath
                      ]
                ',
                'parseResultCallback' => 'generatePresignedUrl',
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

    public function generatePresignedUrl(array $paths): array
    {
        $presigned = [];

        foreach ($paths as $path) {
            $presigned[] = Helper::generatePresignedUrl($path);
        }

        return $presigned;
    }
}
