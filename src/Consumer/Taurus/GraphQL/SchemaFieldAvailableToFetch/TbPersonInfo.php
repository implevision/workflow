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

    public function __construct()
    {
        $this->fieldMapping = $this->initializeFieldMapping();
        $this->queryName = 'producer';
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
        $fieldMapping = [
            'producer' => [
                'GraphQLschemaToReplace' => [
                    'producer' => null,
                ],
                'jqFilter' => '.producer',
            ],

            'AgencyFloodCode' => [
                'GraphQLschemaToReplace' => [
                    'agencyFloodCode' => null,
                ],
                'jqFilter' => '.producer.agencyFloodCode',
            ],

            'AgencyName' => [
                'GraphQLschemaToReplace' => [
                    'agencyName' => null,
                ],
                'jqFilter' => '.producer.agencyName',
            ],

            'DBAName' => [
                'GraphQLschemaToReplace' => [
                    'dbaName' => null,
                ],
                'jqFilter' => '.producer.dbaName',
            ],

            'EftPayeesName' => [
                'GraphQLschemaToReplace' => [
                    'eftPayeesName' => null,
                ],
                'jqFilter' => '.producer.eftPayeesName',
            ],

            'AgencyStatus' => [
                'GraphQLschemaToReplace' => [
                    'agencyStatus' => null,
                ],
                'jqFilter' => '.producer.agencyStatus',
            ],

            'FeinSsnNo' => [
                'GraphQLschemaToReplace' => [
                    'feinSsnNo' => null,
                ],
                'jqFilter' => '.producer.feinSsnNo',
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
            'jqFilter' => '.producer.brandedCompany[].company.companyName',
        ];

        $fieldMapping['CompanyName'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'Contact' => null,
                ],
            ],
            'jqFilter' => '.producer.personContacts[0].Contact',
        ];


        $fieldMapping['ContactEmail'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'contactEmail' => null,
                ],
            ],
            'jqFilter' => '.producer.personContacts[0].contactEmail',
        ];

        $fieldMapping['ContactPhone'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'contactPhone' => null,
                ],
            ],
            'jqFilter' => '.producer.personContacts[0].contactPhone',
        ];

        $fieldMapping['SettlementCode'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => [
                        'settlement_code' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producer.personAddInfos[0].metadata.settlement_code',
        ];

        $fieldMapping['TaxType'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => [
                        'tax_type' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producer.personAddInfos[0].metadata.tax_type',
        ];

        $fieldMapping['CorpStatus'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => [
                        's_CorpStatus' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producer.personAddInfos[0].metadata.s_CorpStatus',
        ];

        $fieldMapping['UWAssign'] = [
            'GraphQLschemaToReplace' => [
                'roles' => [
                    'uWAssign' => [
                        'screenName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producer.roles[0].uWAssign.screenName',
        ];

        $fieldMapping['ServiceRep'] = [
            'GraphQLschemaToReplace' => [
                'roles' => [
                    'serviceRep' => [
                        'screenName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producer.roles[0].serviceRep.screenName',
        ];

        $fieldMapping['ManagerName'] = [
            'GraphQLschemaToReplace' => [
                'managers' => [
                    [
                        'managerPerson' => [
                            's_FirstName' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.producer.managers[0].managerPerson.s_FirstName',
        ];

        $fieldMapping['ManagerPhone'] = [
            'GraphQLschemaToReplace' => [
                'managers' => [
                    [
                        'managerPerson' => [
                            'phoneInfo' => [
                                [
                                    'phoneNumber' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.producer.managers[0].managerPerson.phoneInfo[0].phoneNumber',
        ];

        $fieldMapping['ManagerEmail'] = [
            'GraphQLschemaToReplace' => [
                'managers' => [
                    [
                        'managerPerson' => [
                            'emailInfo' => [
                                [
                                    'email' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.producer.managers[0].managerPerson.emailInfo[0].email',
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
            'jqFilter' => '.producer.licenseManagers[]',
        ];

        $fieldMapping['OriginatingAddress'] = [
            'GraphQLschemaToReplace' => [
                'al3Details' => [
                    'metadata' => [
                        'flood' => [
                            'originating_addr' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.producer.al3Details[].metadata[].flood.originating_addr',
        ];

        $fieldMapping['LastFourDigitAccountNumber'] = [
            'GraphQLschemaToReplace' => [
                'accounts' => [
                    'achConfigurations' => [
                        'lastFourDigitAccountNumber' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producer.accounts[].achConfigurations[].lastFourDigitAccountNumber',
        ];

        $fieldMapping['TodayDate'] = [
            'GraphQLschemaToReplace' => [
                'todayDate' => null,
            ],
            'jqFilter' => '.producer.todayDate',
        ];

        $fieldMapping['AgentPortalUrl'] = [
            'GraphQLschemaToReplace' => [
                'agentInfo' => [
                    'agentUrl' => null,
                ],
            ],
            'jqFilter' => '.producer.agentInfo.agentUrl',
        ];

        $fieldMapping['WyoUpn'] = [
            'GraphQLschemaToReplace' => [
                'wyoUpn' => null,
            ],
            'jqFilter' => '.producer.wyoUpn',
        ];

        $fieldMapping['Naic'] = [
            'GraphQLschemaToReplace' => [
                'referenceNo' => null,
            ],
            'jqFilter' => '.producer.referenceNo',
            'parseResultCallback' => 'parseNaic',
        ];

        $fieldMapping['User'] = [
            'GraphQLschemaToReplace' => [
                'userAgents' => [
                    'user' => [
                        'id' => null,
                        'screenName' => null,
                        'level' => [
                            'UserLevel_Name' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.producer.userAgents[]',
            'parseResultCallback' => 'parseFirstUser',
        ];

        return $fieldMapping;
    }

    private function parseAddress($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $address = [
            'addressLine1' => ($addressArr['houseNo'] ?? '') . ' ' . ($addressArr['streetName'] ?? ($addressArr['addressLine1'] ?? '')),
            'city' => $addressArr['tbCity']['name'] ?? null,
            // 'county' => $addressArr['tbCounty']['name'] ?? null,
            'state' => $addressArr['tbState']['name'] ?? null,
            'postalCode' => $addressArr['postalCode'] ?? null,
        ];

        if (! empty($address['postalCode']) && ! empty($addressArr['postalCodeSuffix'])) {
            $address['postalCode'] .= ' - ' . $addressArr['postalCodeSuffix'];
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

    public function parseNaic($referenceNo)
    {
        $holdingCompanyDetail = Helper::getHoldingCompanyDetail();
        $tenant = getTenant();

        return sprintf('%s%s%s', ucfirst(substr($tenant, 0, 1)), $holdingCompanyDetail['naic_number'], $referenceNo);
    }

    public function parseFirstUser($userAgent)
    {
        $user = $userAgent['user'] ?? null;

        if (! $user) {
            return null;
        }

        return [
            'UserId' => $user['id'] ?? null,
            'UserScreenName' => $user['screenName'] ?? null,
            'UserLevelName' => $user['level']['UserLevel_Name'] ?? null,
        ];
    }
}