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
                    'policy' => [
                        'docuploadinfo' => [
                            'doctypes' => [
                                'docTypeCode' => null,
                            ],
                            'docUploadDocInfoRel' => [
                                'docUploadReference' => [
                                    'tableMasters' => [
                                        'tableName' => null,
                                    ],
                                ],
                                'docInfo' => [
                                    'docPath' => null,
                                    'docName' => null,
                                ],
                            ],
                        ],
                    ],
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
                      | .docUploadReference.tableRefId as $tableRefId
                      | .docInfo[]
                      | { 
                          name: .docName, 
                          path: .docPath,
                          tableRefId: $tableRefId
                        }
                    ]
                ',
                'parseResultCallback' => 'generatePresignedUrl',
            ],
            'NameAsOnTitle' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'insuredPersonInfo' => [
                            'fullName' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.insuredPersonInfo.fullName',
            ],
            'InsuredPropertyAddress' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'insuredPersonInfo' => [
                            'TbPersonaddress' => $addressStructure,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.insuredPersonInfo.TbPersonaddress[] | select(.isDefaultAddress == "Y" and .addressTypeCode == "Location")',
                'parseResultCallback' => 'parsePropertyAddress',
            ],
            'PolicyExpirationDate' => [
                'GraphQLschemaToReplace' => [
                    'transactionEffectiveToDate' => null,
                ],
                'jqFilter' => '.policyQuery.transactionEffectiveToDate',
                'parseResultCallback' => 'formatDate',
            ],
            'TodaysDate' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'todaysDate' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.todaysDate',
                'parseResultCallback' => 'getTodaysDate',
            ],
            'AgentName' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'agentInfo' => [
                            'fullName' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.agentInfo.fullName',
            ],
            'AgentEmail' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'agentInfo' => [
                            'emailInfo' => [
                                'email' => null,
                                'isDefault' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '[.policyQuery.policy.agentInfo.emailInfo[0] | select(.isDefault == "Y")]',
                'parseResultCallback' => 'parseInsuredPersonEmail',
            ],
            'AgentId' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'agentInfo' => [
                            'personUniqueId' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.agentInfo.personUniqueId',
            ],
            'InsuredEmail' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'insuredPersonInfo' => [
                            'emailInfo' => [
                                'email' => null,
                                'isDefault' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '[.policyQuery.policy.insuredPersonInfo.emailInfo[0] | select(.isDefault == "Y")]',
                'parseResultCallback' => 'parseInsuredPersonEmail',
            ],
            'InsuredPhoneNumber' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'insuredPersonInfo' => [
                            'phoneInfo' => [
                                'phoneNumber' => null,
                                'isDefault' => null,
                            ],
                        ],
                    ],
                ],
                'jqFilter' => '[.policyQuery.policy.insuredPersonInfo.phoneInfo[0] | select(.isDefault == "Y")]',
                'parseResultCallback' => 'parseInsuredPersonPhone',
            ],
            'TermStartDate' => [
                'GraphQLschemaToReplace' => [
                    'policyTermMaster' => [
                        'termStartDate' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.policyTermMaster.termStartDate',
                'parseResultCallback' => 'formatDate',
            ],
            'TermEndDate' => [
                'GraphQLschemaToReplace' => [
                    'policyTermMaster' => [
                        'termEndDate' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.policyTermMaster.termEndDate',
                'parseResultCallback' => 'formatDate',
            ],
            'ProductName' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'product' => [
                            'productName' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.product.productName',
            ],
            'TransactionType' => [
                'GraphQLschemaToReplace' => [
                    'policyRiskTransactionType' => [
                        'transactionTypeScreenName' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.policyRiskTransactionType.transactionTypeScreenName',
            ],
            'TransactionSubType' => [
                'GraphQLschemaToReplace' => [
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
                'jqFilter' => '.policyQuery',
                'parseResultCallback' => 'transactionSubTypeScreenNameResolver',
            ],
            'WaitingPeriod' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'policyWaitingPeriod' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.policyWaitingPeriod',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'RenewalIndicator' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'renewalTypeCode' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.renewalTypeCode',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'BillTo' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'accountMaster' => [
                            'billToType' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.accountMaster.billToType',
                'parseResultCallback' => 'parseBillTo',
            ],
            'UnderWriterApplicationStatus' => [
                'GraphQLschemaToReplace' => [
                    'policy' => [
                        'policyApplicationMaster' => [
                            'underwriterApplicationStatusTypeCode' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.policyQuery.policy.policyApplicationMaster.underwriterApplicationStatusTypeCode',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'TransactionEffectiveDate' => [
                'GraphQLschemaToReplace' => [
                    'transactionEffectiveFromDate' => null,
                ],
                'jqFilter' => '.policyQuery.transactionEffectiveFromDate',
                'parseResultCallback' => 'formatDate',
            ],
            'TotalPremium' => [
                'GraphQLschemaToReplace' => [
                    'totalPremium' => null,
                ],
                'jqFilter' => '.policyQuery.totalPremium',
                'parseResultCallback' => 'formatCurrency',
            ],
            'ReplacementCost' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'replacementCost' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.replacementCost',
                'parseResultCallback' => 'formatCurrency',
            ],
            'IsPolicyholderOwnerOrTenant' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'isPolicyholderOwnerOrTenant' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.isPolicyholderOwnerOrTenant',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'IsPolicyRentalProperty' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'isRentalProperty' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.isRentalProperty',
                'parseResultCallback' => 'parseYesNoDisplayName',
            ],
            'IsPolicyholderCondominiumAssociation' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'condoOwnership' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.condoOwnership',
                'parseResultCallback' => 'parseYesNoDisplayName',
            ],
            'CommunityNumber' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'communityNumber' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.communityNumber',
            ],
            'PanelNumber' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'panelNumber' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.panelNumber',
            ],
            'MapSuffix' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'mapSuffix' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.mapSuffix',
            ],
            'FloodZone' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'floodZone' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.floodZone',
            ],
            'CountyName' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'countyName' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.countyName',
            ],
            'InitialFirmDate' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'initialFirmDate' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.initialFirmDate',
                'parseResultCallback' => 'formatDate',
            ],
            'CurrentFirmDate' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'currentFirmDate' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.currentFirmDate',
                'parseResultCallback' => 'formatDate',
            ],
            'CurrentBaseFloodElevation' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'baseElevation' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.baseElevation',
                'parseResultCallback' => 'formatNumber',
            ],
            'IsBuildingLocatedInCoastalBarrierResourcesSystemArea' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'isCBRSorOPA' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.isCBRSorOPA',
                'parseResultCallback' => 'parseYesNoDisplayName',
            ],
            'ConstructionDate' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'dateOfConstruction' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.dateOfConstruction',
                'parseResultCallback' => 'formatDate',
            ],
            'OccupancyType' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'occupancyType' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.occupancyType',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'BuildingDescription' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'buildingUse' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.buildingUse',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'FoundationType' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'foundationType' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.foundationType',
                'parseResultCallback' => 'parseAppCodeNameToDisplayName',
            ],
            'TotalSquareFootage' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'totalSquareFootage' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.totalSquareFootage',
                'parseResultCallback' => 'formatNumber',
            ],
            'NumberOfFloors' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'numberOfFloors' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.numberOfFloors',
                'parseResultCallback' => 'formatNumber',
            ],
            'LoanClosingDate' => [
                'GraphQLschemaToReplace' => [
                    'riskAdditionalFloodInfo' => [
                        'floodLoanClosingDate' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.riskAdditionalFloodInfo.floodLoanClosingDate',
                'parseResultCallback' => 'formatDate',
            ],
            'ECCertificateSignatureDate' => [
                'GraphQLschemaToReplace' => [
                    'elevationCertificate' => [
                        'certificateDate' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.elevationCertificate.certificateDate',
                'parseResultCallback' => 'formatDate',
            ],
            'DiagramNumber' => [
                'GraphQLschemaToReplace' => [
                    'elevationCertificate' => [
                        'buildingDiagramNoCode' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.elevationCertificate.buildingDiagramNoCode',
            ],
            'TopOfBottomFloorInFeet' => [
                'GraphQLschemaToReplace' => [
                    'elevationCertificate' => [
                        'topOfBottomFloor' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.elevationCertificate.topOfBottomFloor',
                'parseResultCallback' => 'formatNumber',
            ],
            'TopOfNextHigherFloorInFeet' => [
                'GraphQLschemaToReplace' => [
                    'elevationCertificate' => [
                        'topOfNextHigherFloor' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.elevationCertificate.topOfNextHigherFloor',
                'parseResultCallback' => 'formatNumber',
            ],
            'LowestAdjacentGrade' => [
                'GraphQLschemaToReplace' => [
                    'elevationCertificate' => [
                        'lowestAdjacentGrade' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.elevationCertificate.lowestAdjacentGrade',
                'parseResultCallback' => 'formatNumber',
            ],
            'AccountingDate' => [
                'GraphQLschemaToReplace' => [
                    'accountingDate' => null,
                ],
                'jqFilter' => '.policyQuery.accountingDate',
                'parseResultCallback' => 'formatDate',
            ],
            'EffectiveDate' => [
                'GraphQLschemaToReplace' => [
                    'policyTermMaster' => [
                        'termStartDate' => null,
                    ],
                ],
                'jqFilter' => '.policyQuery.policyTermMaster.termStartDate',
                'parseResultCallback' => 'formatDate',
            ],
            'WYOCompanyName' => [
                'GraphQLschemaToReplace' => [
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
                'jqFilter' => '.policyQuery.tbAccountMaster.TbPersoninfo.brandedCompany[]',
                'parseResultCallback' => 'parseCompanyName',
            ],
        ];

        $fieldMapping['InsuredMailingAddress'] = [
            'GraphQLschemaToReplace' => $fieldMapping['InsuredPropertyAddress']['GraphQLschemaToReplace'],
            'jqFilter' => '.policyQuery.policy.insuredPersonInfo.TbPersonaddress[] | select(.addressTypeCode == "Mailing")',
            'parseResultCallback' => 'parseMailingAddress',
        ];

        $fieldMapping['PrimaryMortgageeName'] = [
            'GraphQLschemaToReplace' => [
                'mortgageeInfo' => [
                    'mortgageeType' => null,
                    'mortgageePersonInfo' => [
                        'fullName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.policyQuery.mortgageeInfo[] | select(.mortgageeType == "PRIMARY")',
            'parseResultCallback' => 'parsePrimaryMortgageeName',
        ];

        $fieldMapping['PrimaryMortgageeLoanNumber'] = [
            'GraphQLschemaToReplace' => [
                'mortgageeInfo' => [
                    'mortgageeType' => null,
                    'loanNumber' => null,
                ],
            ],
            'jqFilter' => '.policyQuery.mortgageeInfo[] | select(.mortgageeType == "PRIMARY")',
            'parseResultCallback' => 'parseLoanNumber',
        ];

        $fieldMapping['PrimaryMortgageeAddress'] = [
            'GraphQLschemaToReplace' => [
                'mortgageeInfo' => [
                    'mortgageeType' => null,
                    'mortgageeAddress' => $addressStructure,
                ],
            ],
            'jqFilter' => '.policyQuery.mortgageeInfo[] | select(.mortgageeType == "PRIMARY")',
            'parseResultCallback' => 'parsePrimaryMortgageeAddress',
        ];

        $fieldMapping['MortgageeInfo'] = [
            'GraphQLschemaToReplace' => [
                'mortgageeInfo' => [
                    'mortgageeType' => null,
                    'loanNumber' => null,
                    'mortgageeAddress' => $addressStructure,
                    'mortgageePersonInfo' => [
                        'fullName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.policyQuery.mortgageeInfo[]',
            'parseResultCallback' => 'parseMortgageeInfo',
        ];

        $fieldMapping['InsuredPortal'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getInsuredPortalUrl',
        ];

        $fieldMapping['AdditionalInsuredName'] = [
            'GraphQLschemaToReplace' => [
                'additionalInterestInfo' => [
                    'partyInterestCode' => null,
                    'additionalPersonInfo' => [
                        'fullname' => null,
                    ],
                ],
            ],
            'jqFilter' => '.policyQuery.additionalInterestInfo[] | select(.partyInterestCode == "ADDITIONALINSURED")',
            'parseResultCallback' => 'parseAdditionalInsuredName',
        ];

        $fieldMapping['CompanyLogo'] = [
            'GraphQLschemaToReplace' => [
                'tbAccountMaster' => [
                    'TbPersoninfo' => [
                        'brandedCompany' => [
                            'company' => [
                                'logo' => null,
                                'publicLogo' => null,
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.policyQuery.tbAccountMaster.TbPersoninfo.brandedCompany[]',
            'parseResultCallback' => 'resolveCompanyLogoUrl',
        ];

        return $fieldMapping;
    }

    public function parsePremiumDue($premiumChangeAndFeesArr)
    {
        $premiumDue = 0;
        // Need to update for other products
        if (is_array($premiumChangeAndFeesArr)) {
            $premiumChange = $premiumChangeAndFeesArr['premiumChange'] ?? 0;
            $policyFees = $premiumChangeAndFeesArr['policyFees'] ?? 0;
            $premiumDue = $premiumChange + $policyFees;
        }

        return $this->formatCurrency($premiumDue);
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

    public function parsePropertyAddress($addressArr)
    {
        return $this->parseAddress($addressArr);
    }

    public function getTodaysDate(): string
    {
        return Helper::getTodaysDate();
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

    public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }

    public function parseAppCodeNameToDisplayName($appCodeName)
    {
        return Helper::parseAppCodeNameToDisplayName($appCodeName);
    }

    public function parseBillTo($appCodeName)
    {
        $ddGroup = 'BILLTOFLOOD'; // TODO: Confirm whether 'BILLTO' should be used for non-flood products
        $label = Helper::parseAppCodeNameToDisplayNameUsingDDGroup($ddGroup, $appCodeName);

        return $label;
    }

    public function generatePresignedUrl(array $documents): array
    {
        return array_values(array_map(
            function ($doc) {
                return [
                    'name' => $this->formatFileName($doc['name']),
                    'path' => Helper::generatePresignedUrl($doc['path']),
                ];
            },
            array_filter($documents, function ($doc) {
                return isset($doc['tableRefId'], $doc['name'])
                    && str_contains($doc['name'], (string) $doc['tableRefId']);
            })
        ));
    }

    public function parseYesNoDisplayName($value)
    {
        return Helper::parseYesNoDisplayName($value);
    }

    public function formatCurrency($amount)
    {
        return Helper::formatCurrency($amount);
    }

    public function formatNumber($number)
    {
        return Helper::formatNumber($number);
    }

    public function parseCompanyName($brandedCompanyArr)
    {
        if (is_array($brandedCompanyArr) && ! empty($brandedCompanyArr['company']['companyName'])) {
            return $brandedCompanyArr['company']['companyName'];
        }

        return null;
    }

    public function transactionSubTypeScreenNameResolver($policyData)
    {
        $productCode = $policyData['policy']['product']['productCode'] ?? null;
        $isNfipProduct = Helper::isNfipProduct($productCode);

        if (
            $policyData['policyRiskTransactionType']['policyRiskTransactionTypeCode'] === 'ENDORSE'
            &&
            $isNfipProduct
        ) {
            $reasonCode = $policyData['floodTransactionSubType']['reasonCode'] ?? '';

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
            return $policyData['policyRiskTransactionSubType']['transactionSubTypeScreenName'] ?? null;
        }
    }

    public function parsePrimaryMortgageeName($mortgagee)
    {
        return $mortgagee['mortgageePersonInfo']['fullName'] ?? null;
    }

    public function parseLoanNumber($mortgagee)
    {
        return $mortgagee['loanNumber'] ?? null;
    }

    public function parsePrimaryMortgageeAddress($mortgagee)
    {
        return $this->parseAddress($mortgagee['mortgageeAddress'] ?? []);
    }

    public function parseMortgageeInfo($mortgagees)
    {
        if (is_string($mortgagees)) {
            // Handle case where multiple JSON objects are concatenated without an array wrapper
            $objects = [];
            $pattern = '/\{(?:[^{}]|(?R))*\}/m';
            if (preg_match_all($pattern, $mortgagees, $matches)) {
                foreach ($matches[0] as $jsonObj) {
                    $decoded = json_decode($jsonObj, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $objects[] = $decoded;
                    }
                }
                $mortgagees = $objects;
            } else {
                // fallback: wrap as single element array
                $decoded = json_decode($mortgagees, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $mortgagees = [$decoded];
                } else {
                    $mortgagees = [$mortgagees];
                }
            }
        }

        $mortgageesList = [];

        foreach ($mortgagees as $mortgagee) {
            $mortgageeType = $mortgagee['mortgageeType'] ?? null;
            $loanNumber = $mortgagee['loanNumber'] ?? null;
            $mortgageeFullName = $mortgagee['mortgageePersonInfo']['fullName'] ?? null;
            $mortgageeAddress = $this->parseAddress($mortgagee['mortgageeAddress'] ?? []);

            $mortgageeParts = [
                'mortgageeType' => $mortgageeType,
                'loanNumber' => $loanNumber,
                'mortgageeFullName' => $mortgageeFullName,
                'mortgageeAddress' => $mortgageeAddress,
            ];

            $mortgageesList[] = $mortgageeParts;
        }

        return $mortgageesList;
    }

    public function getInsuredPortalUrl()
    {
        return Helper::createPortalURL('InsuredPortal');
    }

    public function parseAdditionalInsuredName($additionalInterest)
    {
        return $additionalInterest['additionalPersonInfo']['fullname'] ?? null;
    }

    public static function formatFileName(?string $fileName): string
    {
        if (empty($fileName)) {
            return '';
        }

        return pathinfo($fileName, PATHINFO_FILENAME);
    }

    public function resolveCompanyLogoUrl($brandedCompanyArr)
    {
        return Helper::parseCompanyLogo($brandedCompanyArr);
    }
}
