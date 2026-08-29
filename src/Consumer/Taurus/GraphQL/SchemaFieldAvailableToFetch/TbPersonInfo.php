<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

class TbPersonInfo extends AbstractSchema
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

    /**
     * @var string|null The path of the query associated with this class.
     */
    protected $queryPath;

    public function __construct()
    {
        $this->queryName = 'producersQuery';
        $this->queryPath = '.'.$this->queryName;
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the Producer.
     *
     * This method returns an associative array that maps the fields
     * of the Producer to their corresponding values or attributes.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        if (empty($this->fieldMapping)) {
            $this->fieldMapping = $this->initializeFieldMapping();
        }

        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the Producer.
     *
     * This method returns the name of the GraphQL query that can be used
     * to fetch data related to the Producer.
     *
     * @return string The name of the GraphQL query for Producer.
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Initializes the field mapping with GraphQL schema for the Producer class.
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
        $appendedPlaceHolders = $this->getAppendedPlaceHolders();

        $fieldMapping = [

            'AgencyFloodCode' => [
                'GraphQLschemaToReplace' => [
                    'agencyFloodCode' => null,
                ],
                'jqFilter' => "{$this->queryPath}.agencyFloodCode",
            ],

            'AgencyName' => [
                'GraphQLschemaToReplace' => [
                    'agencyName' => null,
                ],
                'jqFilter' => "{$this->queryPath}.agencyName",
            ],

            'DBAName' => [
                'GraphQLschemaToReplace' => [
                    'dbaName' => null,
                ],
                'jqFilter' => "{$this->queryPath}.dbaName",
            ],

            'EftPayeesName' => [
                'GraphQLschemaToReplace' => [
                    'eftPayeesName' => null,
                ],
                'jqFilter' => "{$this->queryPath}.eftPayeesName",
            ],

            'AgencyStatus' => [
                'GraphQLschemaToReplace' => [
                    'agencyStatus' => null,
                ],
                'jqFilter' => "{$this->queryPath}.agencyStatus",
            ],

            'FeinSsnNo' => [
                'GraphQLschemaToReplace' => [
                    'feinSsnNo' => null,
                ],
                'jqFilter' => "{$this->queryPath}.feinSsnNo",
            ],

            'FullLegalName' => [
                'GraphQLschemaToReplace' => [
                    'fullName' => null,
                ],
                'jqFilter' => "{$this->queryPath}.fullName",
            ],
        ];

        $fieldMapping['BrandedCompany'] = [
            'GraphQLschemaToReplace' => [
                'brandedCompany' => [
                    'company' => [
                        'companyName' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.brandedCompany[0].company.companyName",
        ];

        $fieldMapping['ContactName'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'contactName' => null,
                ],
            ],
            'jqFilter' => "{$this->queryPath}.personContacts[0].contactName",
        ];

        $fieldMapping['ContactEmail'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'contactEmail' => null,
                ],
            ],
            'jqFilter' => "{$this->queryPath}.personContacts[0].contactEmail",
        ];

        $fieldMapping['ContactPhone'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'contactPhone' => null,
                ],
            ],
            'jqFilter' => "{$this->queryPath}.personContacts[0].contactPhone",
        ];

        $fieldMapping['SettlementCode'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => null,
                ],
            ],
            'jqFilter' => "{$this->queryPath}.personAddInfos[0].metadata.settlement_code",
        ];

        $fieldMapping['TaxType'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => null,
                ],
            ],
            'jqFilter' => "{$this->queryPath}.personAddInfos[0].metadata.tax_type",
        ];

        $fieldMapping['CorpStatus'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => null,
                ],
            ],
            'jqFilter' => "{$this->queryPath}.personAddInfos[0].metadata.s_CorpStatus",
        ];

        $fieldMapping['UWAssign'] = [
            'GraphQLschemaToReplace' => [
                'roles' => [
                    'uWAssign' => [
                        'screenName' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.roles[0].uWAssign.screenName",
        ];

        $fieldMapping['ServiceRep'] = [
            'GraphQLschemaToReplace' => [
                'roles' => [
                    'serviceRep' => [
                        'screenName' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.roles[0].serviceRep.screenName",
        ];

        $fieldMapping['ManagerName'] = [
            'GraphQLschemaToReplace' => [
                'managers' => [
                    'managerPerson' => [
                        'firstName' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.managers[0].managerPerson.firstName",
        ];

        $fieldMapping['ManagerPhone'] = [
            'GraphQLschemaToReplace' => [
                'managers' => [
                    'managerPerson' => [
                        'phoneInfo' => [
                            'phoneNumber' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.managers[0].managerPerson.phoneInfo[0].phoneNumber",
        ];

        $fieldMapping['ManagerEmail'] = [
            'GraphQLschemaToReplace' => [
                'managers' => [
                    'managerPerson' => [
                        'emailInfo' => [
                            'email' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.managers[0].managerPerson.emailInfo[0].email",
        ];

        $fieldMapping['LicenseManagers'] = [
            'GraphQLschemaToReplace' => [
                'licenseManagers' => [
                    'agencyNPN' => null,
                    'applicationDate' => null,
                    'appointed' => null,
                    'expirationDate' => null,
                    'issueDate' => null,
                    'licenseNumber' => null,
                    'licenseType' => null,
                    'state' => null,
                ],
            ],
            'jqFilter' => "{$this->queryPath}.licenseManagers[]",
        ];

        $fieldMapping['OriginatingAddress'] = [
            'GraphQLschemaToReplace' => [
                'al3Details' => [
                    'metadata' => null,
                ],
            ],
            'jqFilter' => "{$this->queryPath}.al3Details[0].metadata[0].flood.originating_addr",
        ];

        $fieldMapping['LastFourDigitAccountNumber'] = [
            'GraphQLschemaToReplace' => [
                'accounts' => [
                    'achConfigurations' => [
                        'lastFourDigitOfAccountNumber' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.accounts[0].achConfigurations[0].lastFourDigitOfAccountNumber",
        ];

        $fieldMapping['TodayDate'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getTodaysDate',
        ];

        $fieldMapping['AgentPortalUrl'] = [
            'GraphQLschemaToReplace' => [
                'agentInfo' => [
                    'agentUrl' => null,
                ],
            ],
            'jqFilter' => "{$this->queryPath}.agentInfo.agentUrl",
        ];

        $fieldMapping['WyoUpn'] = [
            'GraphQLschemaToReplace' => [
                'wyoUpn' => null,
            ],
            'jqFilter' => "{$this->queryPath}.wyoUpn",
        ];

        $fieldMapping['User'] = [
            'GraphQLschemaToReplace' => [
                'userAgents' => [
                    'user' => [
                        'id' => null,
                        'screenName' => null,
                        'level' => [
                            'userLevelName' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgents[]",
            'parseResultCallback' => 'parseFirstUser',
        ];

        $mailingAddressStructure = [
            'addresses' => [
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
            ],
        ];

        $fieldMapping['MailingAddress'] = [
            'GraphQLschemaToReplace' => $mailingAddressStructure,
            'jqFilter' => "{$this->queryPath}.addresses[] | select(.addressTypeCode == \"MAILING\")",
            'parseResultCallback' => 'parseFullMailingAddress',
        ];

        $fieldMapping['LocationAddress'] = [
            'GraphQLschemaToReplace' => $mailingAddressStructure,
            'jqFilter' => "{$this->queryPath}.addresses[] | select(.addressTypeCode == \"LOCATION\")",
            'parseResultCallback' => 'parseFullLocationAddress',
        ];

        $fieldMapping['MailingAddressLine'] = [
            'GraphQLschemaToReplace' => $mailingAddressStructure,
            'jqFilter' => "{$this->queryPath}.addresses[] | select(.addressTypeCode == \"MAILING\")",
            'parseResultCallback' => 'parseMailingAddressLine',
        ];

        $fieldMapping['MailingCityStateZip'] = [
            'GraphQLschemaToReplace' => $mailingAddressStructure,
            'jqFilter' => "{$this->queryPath}.addresses[] | select(.addressTypeCode == \"MAILING\")",
            'parseResultCallback' => 'parseMailingCityStateZip',
        ];

        $fieldMapping['W9FormAddress'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        ...$mailingAddressStructure,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agency.addresses[] | select(.addressTypeCode == \"MAILING\")",
            'parseResultCallback' => 'parseW9FormAddress',
        ];

        $fieldMapping['W9FormCityStateZip'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        ...$mailingAddressStructure,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agency.addresses[] | select(.addressTypeCode == \"MAILING\")",
            'parseResultCallback' => 'parseW9FormCityStateZip',
        ];

        $fieldMapping['W9FormFeinSsnNo'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        'feinSsnNo' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agency.feinSsnNo",
            'parseResultCallback' => 'parseW9FormFeinSsnNo',
        ];
        
        // agent 
        $fieldMapping['AgentFirstName'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'firstName' => null,
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.firstName',
        ];

        $fieldMapping['AgentLastName'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'lastName' => null,
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.lastName',
        ];

        
        $fieldMapping['AgentFloodCode'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'agencyFloodCode' => null,
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.agencyFloodCode',
        ];

         $fieldMapping['AgentWYOUpn'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'wyoUpn' => null,
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.wyoUpn',
        ];

         $fieldMapping['AgentStatus'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'agencyStatus' => null,
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.agencyStatus',
        ];

        
         $fieldMapping['AgentEmail'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'emailInfo' => [
                            'email' => null
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.emailInfo[0].email',
        ];

         $fieldMapping['WYOAgentCode'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'personAddInfos' => [
                            'wyoAgencyAgentCode' => null
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.personAddInfos[0].wyoAgencyAgentCode',
        ];

        $fieldMapping['PreviousWYOAgentCode'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'personAddInfos' => [
                            'previousWyoAgencyAgentCode' => null
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.personAddInfos[0].previousWyoAgencyAgentCode',
        ];


        
        $fieldMapping['AgentNewBusinessSuspendDate'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'personAddInfos' => [
                            'metadata' => null
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.personAddInfos[0].metadata.newbusiness_suspend_date',
        ];

        $fieldMapping['AgentResidentStateName'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'personAddInfos' => [
                            'residentStateName' => null
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.personAddInfos[0].residentStateName',
        ];

        $fieldMapping['AgentResidentStateId'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'personAddInfos' => [
                            'metadata' => null
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.personAddInfos[0].metadata.resident_state_id',
        ];

        $fieldMapping['UserLevel'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'user' => [
                            'level' => [
                                'userLevelName' => null
                            ]
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.user.level.userLevelName',
        ];

   
        $fieldMapping['AgentPhoneNumber'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'user' => [
                            'phoneNumber'  => null
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.user.phoneNumber',
        ];

        $fieldMapping['DebarmentApprovalDate'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'user' => [
                            'userSuspension'  => [
                                'debarmentApprovalDate' => null
                            ]
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.user.userSuspension.debarmentApprovalDate',
        ];

     
        $fieldMapping['DebarmentExpirationDate'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'user' => [
                            'userSuspension'  => [
                                'debarmentExpirationDate' => null
                            ]
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.user.userSuspension.debarmentExpirationDate',
        ];
   
        $fieldMapping['DebarredDate'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                  'agent' => [
                        'user' => [
                            'userSuspension'  => [
                                'debarredDate' => null
                            ]
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agent.user.userSuspension.DebarredDate',
        ];

        $fieldMapping['AgentAgencyName'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        'agencyName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agency.agencyName',
        ];

        $fieldMapping['AgentAgencyFloodCode'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        'agencyFloodCode' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agency.agencyFloodCode',
        ];

        $fieldMapping['AgentAgencyDbaName'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        'dbaName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agency.dbaName',
        ];

        $fieldMapping['AgentAgencyFeinSsnNo'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        'feinSsnNo' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agency.feinSsnNo',
        ];

        $fieldMapping['AgentAgencyWyoCode'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        'personAddInfos' => [
                            'wyoAgencyAgentCode' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agency.personAddInfos[0].wyoAgencyAgentCode',
        ];

        $fieldMapping['AgentAgencyEftPayeesName'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        'eftPayeesName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.userAgent.agency.eftPayeesName',
        ];

        $targetAgentStatementMasterPK = isset($appendedPlaceHolders['AgentStatementMasterPK']) ? $appendedPlaceHolders['AgentStatementMasterPK'] : null;

        $fieldMapping['AttachStatementSheet'] = [
            'GraphQLschemaToReplace' => [
                'accounts' => [
                    'agentStatementMaster' => [
                        'agentStatementMasterPK' => null,
                        'path' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.accounts[].agentStatementMaster[] | select((.agentStatementMasterPK|tostring) == ({$targetAgentStatementMasterPK}|tostring))",
            'parseResultCallback' => 'generatePresignedUrlForStatementSheet',
        ];

        $fieldMapping['WYOCompanyName'] = [
            'GraphQLschemaToReplace' => [
                'brandedCompany' => [
                    'company' => [
                        'companyName' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}",
            'parseResultCallback' => 'parseCompanyName',
        ];

        $fieldMapping['CompanyLogo'] = [
            'GraphQLschemaToReplace' => [
                'brandedCompany' => [
                    'company' => [
                        'logo' => null,
                        'publicLogo' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}",
            'parseResultCallback' => 'resolveCompanyLogoUrl',
        ];

        $fieldMapping['AgentMailingAddress'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agency' => [
                        ...$mailingAddressStructure,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agency.addresses[] | select(.addressTypeCode == \"MAILING\")",
            'parseResultCallback' => 'parseFullMailingAddress',
        ];

        $fieldMapping['AgentCommissionPercentageForAgreement'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'parseAgentCommissionPercentageForAgreement',
        ];

        $fieldMapping['AgencyManagerEmail'] = [
            'GraphQLschemaToReplace' => [
                'userAgents' => [
                    'user' => [
                        'email' => null,
                        'level' => [
                            'userLevelCode' => null,
                        ],
                        'userStatus' => null,
                    ],
                ],
            ],
            'jqFilter' => "[{$this->queryPath}.userAgents[] | select(.user.level.userLevelCode == \"PRINCIPLE\" and (.user.userStatus|tostring) == \"111\" and .user.email != null) | .user.email]",
            'parseResultCallback' => 'parseAgencyManagerEmails',
        ];

        $fieldMapping['AgentDashboardURL'] = [
            'GraphQLschemaToReplace' => '',
            'jqFilter' => '',
            'parseResultCallback' => 'getAgentDashboard',
        ];

        $fieldMapping['ProducerTitleForAgreement'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'parseProducerTitleForAgreement',
        ];

        $fieldMapping['BusinessEntityNameForAgreement'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'parseBusinessEntityNameForAgreement',
        ];

        $fieldMapping['BusinessEntityTitleForAgreement'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'parseBusinessEntityTitleForAgreement',
        ];

        return $this->wrapFieldMappingSchemaUnderData($fieldMapping);
    }

    public function parseFirstUser($userAgent)
    {
        $user = $userAgent['user'] ?? null;

        if (! $user) {
            return null;
        }

        return [
            'userId' => $user['id'] ?? null,
            'userScreenName' => $user['screenName'] ?? null,
            'userLevelName' => $user['level']['userLevelName'] ?? null,
        ];
    }

    private function parseFullAddress($addressArr)
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

    public function parseFullMailingAddress($addressArr)
    {
        return $this->parseFullAddress($addressArr);
    }

    public function parseFullLocationAddress($addressArr)
    {
        return $this->parseFullAddress($addressArr);
    }

    public function parseMailingAddressLine($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $parts = array_filter(array_map('trim', [
            $addressArr['addressLine1'] ?? '',
            $addressArr['addressLine2'] ?? '',
            $addressArr['addressLine3'] ?? '',
        ]));

        return implode(', ', $parts) ?: null;
    }

    public function parseMailingCityStateZip($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $parts = array_filter(array_map('trim', [
            $addressArr['tbCity']['name'] ?? '',
            $addressArr['tbState']['name'] ?? '',
            $addressArr['postalCode'] ?? '',
        ]));

        return implode(', ', $parts) ?: null;
    }

    public function parseW9FormAddress($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $parts = array_filter(array_map('trim', [
            $addressArr['addressLine1'] ?? '',
            $addressArr['addressLine2'] ?? '',
        ]));

        return implode(', ', $parts) ?: null;
    }

    public function parseW9FormCityStateZip($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $parts = array_filter(array_map('trim', [
            $addressArr['tbCity']['name'] ?? '',
            $addressArr['tbState']['name'] ?? '',
            $addressArr['postalCode'] ?? '',
        ]));

        return implode(', ', $parts) ?: null;
    }

    public function parseW9FormFeinSsnNo($feinSsnNo)
    {
        if (empty($feinSsnNo)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $feinSsnNo);

        // SSN format: XXX-XX-XXXX — each digit spaced, groups separated by 3 spaces
        if (strlen($digits) === 9) {
            $part1 = implode(' ', str_split(substr($digits, 0, 3)));
            $part2 = implode(' ', str_split(substr($digits, 3, 2)));
            $part3 = implode(' ', str_split(substr($digits, 5, 4)));

            return $part1.'    '.$part2.'   '.$part3;
        }

        return implode(' ', str_split($digits));
    }

    public function getTodaysDate(): string
    {
        return Helper::getTodaysDate();
    }

    public function generatePresignedUrlForStatementSheet(array $agentStatementMasterData): array
    {
        return [
            [
                'name' => 'Commission Statement Sheet',
                'path' => Helper::generatePresignedUrl($agentStatementMasterData['path'] ?? ''),
            ],
        ];
    }

    public function parseCompanyName($response)
    {
        return $this->resolveCompanyDetail($response, 'companyName', 'wyo');
    }

    private function resolveCompanyDetail($response, string $companyKey, string $holdingKey): string
    {
        [$brandedCompanyArr] = $this->extractProducerContext($response);

        $value = $brandedCompanyArr['company'][$companyKey] ?? null;
        if (! empty($value)) {
            return $value;
        }

        return Helper::getHoldingCompanyDetail()[$holdingKey] ?? '';
    }

    private function extractProducerContext($response): array
    {
        $response = is_array($response) ? $response : [];
        $brandedCompany = $response['brandedCompany'] ?? [];

        if (is_array($brandedCompany) && array_key_exists('company', $brandedCompany)) {
            $normalizedBrandedCompany = $brandedCompany;
        } else {
            $normalizedBrandedCompany = is_array($brandedCompany) ? ($brandedCompany[0] ?? []) : [];
        }

        return [
            $normalizedBrandedCompany,
        ];
    }

    public function resolveCompanyLogoUrl($response)
    {
        [$brandedCompanyArr] = $this->extractProducerContext($response);

        return Helper::parseCompanyLogo($brandedCompanyArr);
    }

    public function parseAgentCommissionPercentageForAgreement()
    {
        return match (getTenant()) {
            'advantageflood' => 'Twenty (20%)',
            default => '',
        };
    }

    public function parseAgencyManagerEmails($emails)
    {
        if (empty($emails) || ! \is_array($emails)) {
            return null;
        }

        return implode(',', array_filter(array_unique($emails)));
    }

    public function getAgentDashboard(): string
    {
        return Helper::createPortalURL('AgentPortal').'/dashboard';
    }

    public function parseProducerTitleForAgreement()
    {
        return match (getTenant()) {
            'advantageflood' => 'Producer',
            default => '',
        };
    }

    public function parseBusinessEntityNameForAgreement()
    {
        return match (getTenant()) {
            'advantageflood' => 'Thomas Garner',
            default => '',
        };
    }

    public function parseBusinessEntityTitleForAgreement()
    {
        return match (getTenant()) {
            'advantageflood' => 'CEO - Taurus Services',
            default => '',
        };
    }
}
