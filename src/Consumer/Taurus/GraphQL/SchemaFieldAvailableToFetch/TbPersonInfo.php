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
        $this->queryName = 'producerQuery';
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

            'AgencyFloodCode' => [
                'GraphQLschemaToReplace' => [
                    'agencyFloodCode' => null,
                ],
                'jqFilter' => '.producerQuery.agencyFloodCode',
            ],

            'AgencyName' => [
                'GraphQLschemaToReplace' => [
                    'agencyName' => null,
                ],
                'jqFilter' => '.producerQuery.agencyName',
            ],

            'DBAName' => [
                'GraphQLschemaToReplace' => [
                    'dbaName' => null,
                ],
                'jqFilter' => '.producerQuery.dbaName',
            ],

            'EftPayeesName' => [
                'GraphQLschemaToReplace' => [
                    'eftPayeesName' => null,
                ],
                'jqFilter' => '.producerQuery.eftPayeesName',
            ],

            'AgencyStatus' => [
                'GraphQLschemaToReplace' => [
                    'agencyStatus' => null,
                ],
                'jqFilter' => '.producerQuery.agencyStatus',
            ],

            'FeinSsnNo' => [
                'GraphQLschemaToReplace' => [
                    'feinSsnNo' => null,
                ],
                'jqFilter' => '.producerQuery.feinSsnNo',
            ],

            'FullLegalName' => [
                'GraphQLschemaToReplace' => [
                    'fullLegalName' => null,
                ],
                'jqFilter' => '.producerQuery.fullLegalName',
            ]
        ];

        $fieldMapping['BrandedCompany'] = [
            'GraphQLschemaToReplace' => [
                'brandedCompany' => [
                    'company' => [
                        'companyName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.brandedCompany[0].company.companyName',
        ];

        $fieldMapping['ContactName'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'contactName' => null,
                ],
            ],
            'jqFilter' => '.producerQuery.personContacts[0].contactName',
        ];


        $fieldMapping['ContactEmail'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'contactEmail' => null,
                ],
            ],
            'jqFilter' => '.producerQuery.personContacts[0].contactEmail',
        ];

        $fieldMapping['ContactPhone'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'contactPhone' => null,
                ],
            ],
            'jqFilter' => '.producerQuery.personContacts[0].contactPhone',
        ];

        $fieldMapping['SettlementCode'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => null,
                ],
            ],
            'jqFilter' => '.producerQuery.personAddInfos[0].metadata.settlement_code',
        ];

        $fieldMapping['TaxType'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => null,
                ],
            ],
            'jqFilter' => '.producerQuery.personAddInfos[0].metadata.tax_type',
        ];

        $fieldMapping['CorpStatus'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => null,
                ],
            ],
            'jqFilter' => '.producerQuery.personAddInfos[0].metadata.s_CorpStatus',
        ];

        $fieldMapping['UWAssign'] = [
            'GraphQLschemaToReplace' => [
                'roles' => [
                    'uWAssign' => [
                        'screenName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.roles[0].uWAssign.screenName',
        ];

        $fieldMapping['ServiceRep'] = [
            'GraphQLschemaToReplace' => [
                'roles' => [
                    'serviceRep' => [
                        'screenName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.roles[0].serviceRep.screenName',
        ];

        $fieldMapping['ManagerName'] = [
            'GraphQLschemaToReplace' => [
                'managers' => [
                    'managerPerson' => [
                        'firstName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.managers[0].managerPerson.firstName',
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
            'jqFilter' => '.producerQuery.managers[0].managerPerson.phoneInfo[0].phoneNumber',
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
            'jqFilter' => '.producerQuery.managers[0].managerPerson.emailInfo[0].email',
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
            'jqFilter' => '.producerQuery.licenseManagers[]',
        ];

        $fieldMapping['OriginatingAddress'] = [
            'GraphQLschemaToReplace' => [
                'al3Details' => [
                    'metadata' => null,
                ],
            ],
            'jqFilter' => '.producerQuery.al3Details[0].metadata[0].flood.originating_addr',
        ];

        $fieldMapping['LastFourDigitAccountNumber'] = [
            'GraphQLschemaToReplace' => [
                'accounts' => [
                    'achConfigurations' => [
                        'lastFourDigitOfAccountNumber' => null,
                    ],
                ],
            ],
            'jqFilter' => '.producerQuery.accounts[0].achConfigurations[0].lastFourDigitOfAccountNumber',
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
            'jqFilter' => '.producerQuery.agentInfo.agentUrl',
        ];

        $fieldMapping['WyoUpn'] = [
            'GraphQLschemaToReplace' => [
                'wyoUpn' => null,
            ],
            'jqFilter' => '.producerQuery.wyoUpn',
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
            'jqFilter' => '.producerQuery.userAgents[]',
            'parseResultCallback' => 'parseFirstUser',
        ];

        $mailingAddressStructure = [
            'addresses' => [
                'addressTypeCode' => null,
                'addressLine1' => null,
                'addressLine2' => null,
                'addressLine3' => null,
                'postalCode' => null,
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
            'jqFilter' => '.producerQuery.addresses[] | select(.addressTypeCode == "MAILING")',
            'parseResultCallback' => 'parseFullMailingAddress',
        ];

        $fieldMapping['LocationAddress'] = [
            'GraphQLschemaToReplace' => $mailingAddressStructure,
            'jqFilter' => '.producerQuery.addresses[] | select(.addressTypeCode == "LOCATION")',
            'parseResultCallback' => 'parseFullLocationAddress',
        ];

        $fieldMapping['MailingAddressLine'] = [
            'GraphQLschemaToReplace' => $mailingAddressStructure,
            'jqFilter' => '.producerQuery.addresses[] | select(.addressTypeCode == "MAILING")',
            'parseResultCallback' => 'parseMailingAddressLine',
        ];

        $fieldMapping['MailingCityStateZip'] = [
            'GraphQLschemaToReplace' => $mailingAddressStructure,
            'jqFilter' => '.producerQuery.addresses[] | select(.addressTypeCode == "MAILING")',
            'parseResultCallback' => 'parseMailingCityStateZip',
        ];

        return $fieldMapping;
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

    private function parseFullAddress($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $parts = array_filter(array_map('trim', [
            $addressArr['addressLine1'] ?? '',
            $addressArr['addressLine2'] ?? '',
            $addressArr['addressLine3'] ?? '',
            $addressArr['tbCity']['name'] ?? '',
            $addressArr['tbState']['name'] ?? '',
            $addressArr['postalCode'] ?? '',
        ]));

        return implode(', ', $parts) ?: null;
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
    public function getTodaysDate(): string
    {
        return Helper::getTodaysDate();
    }
}
