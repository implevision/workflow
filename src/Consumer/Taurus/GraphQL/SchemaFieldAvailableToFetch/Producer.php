<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Carbon\Carbon;
use Taurus\Workflow\Consumer\Taurus\Helper;

class Producer
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
                'jqFilter' => '.data.producer',
            ],

            'AgencyFloodCode' => [
                'GraphQLschemaToReplace' => [
                    'agencyFloodCode' => null,
                ],
                'jqFilter' => '.data.producer.agencyFloodCode',
            ],

            'AgencyName' => [
                'GraphQLschemaToReplace' => [
                    'agencyName' => null,
                ],
                'jqFilter' => '.data.producer.agencyName',
            ],

            'DBAName' => [
                'GraphQLschemaToReplace' => [
                    'dbaName' => null,
                ],
                'jqFilter' => '.data.producer.dbaName',
            ],

            'EftPayeesName' => [
                'GraphQLschemaToReplace' => [
                    'eftPayeesName' => null,
                ],
                'jqFilter' => '.data.producer.eftPayeesName',
            ],

            'AgencyStatus' => [
                'GraphQLschemaToReplace' => [
                    'agencyStatus' => null,
                ],
                'jqFilter' => '.data.producer.agencyStatus',
            ],

            'FeinSsnNo' => [
                'GraphQLschemaToReplace' => [
                    'feinSsnNo' => null,
                ],
                'jqFilter' => '.data.producer.feinSsnNo',
            ],
        ];

        $fieldMapping['Roles'] = [
            'GraphQLschemaToReplace' => [
                'roles' => [
                    'uWAssign' => [
                        'screenName' => null,
                    ],
                    'serviceRep' => [
                        'screenName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.data.producer.roles[]',
        ];

        $fieldMapping['PersonAddInfos'] = [
            'GraphQLschemaToReplace' => [
                'personAddInfos' => [
                    'metadata' => [
                        'tax_type' => null,
                        's_CorpStatus' => null,
                        'settlement_code' => null,
                    ],
                ],
            ],
            'jqFilter' => '.data.producer.personAddInfos[].metadata',
        ];

        $fieldMapping['BrandedCompany'] = [
            'GraphQLschemaToReplace' => [
                'brandedCompany' => [
                    'company' => [
                        'companyName' => null,
                    ],
                ],
            ],
            'jqFilter' => '.data.producer.brandedCompany[]',
        ];

        $fieldMapping['PersonContacts'] = [
            'GraphQLschemaToReplace' => [
                'personContacts' => [
                    'Contact' => null,
                    'contactEmail' => null,
                    'contactPhone' => null,
                ],
            ],
            'jqFilter' => '.data.producer.personContacts[]',
        ];

        $fieldMapping['Managers'] = [
            'GraphQLschemaToReplace' => [
                'managers' => [
                    'managerPerson' => [
                        's_FirstName' => null,
                        'phoneInfo' => [
                            'phoneNumber' => null,
                        ],
                        'emailInfo' => [
                            'email' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.data.producer.managers[]',
        ];

        $fieldMapping['Addresses'] = [
            'GraphQLschemaToReplace' => [
                'addresses' => [
                    'addressTypeCode' => null,
                    'addressLine1' => null,
                    'addressLine2' => null,
                    'addressLine3' => null,
                    'addressLine4' => null,
                    'postalCode' => null,
                    'tbCity' => [
                        'name' => null,
                    ],
                    'tbState' => [
                        'name' => null,
                    ],
                    'tbCountry' => [
                        'name' => null,
                    ],
                    'tbCounty' => [
                        'name' => null,
                    ],
                    'webInfo' => [
                        'web' => null,
                    ],
                    'phoneInfo' => [
                        'phoneNumber' => null,
                    ],
                ],
            ],

            'jqFilter' => '.data.producer.addresses[]',

            'parseResultCallback' => 'parseAddress',
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
            'jqFilter' => '.data.producer.licenseManagers[]',
        ];

        return $fieldMapping;
    }

    public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
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

    public function parseAdjustingFirmName($nameArr)
    {
        return last($nameArr);
    }

    public function parseAdjustingFirmAddress($addressArr)
    {
        return $this->parseAddress(last($addressArr));
    }

    public function parseMailingAddress($addressArr)
    {
        return $this->parseAddress($addressArr);
    }

    public function parsePropertyAddress($addressArr)
    {
        return $this->parseAddress($addressArr);
    }

    public function parseAdjustingFirmEmail($emailArr)
    {
        return is_array($emailArr) && count($emailArr) ? (last($emailArr)['email'] ?? null) : null;
    }

    public function parseAdjustingFirmPhone($phoneArr)
    {
        $phone = is_array($phoneArr) && count($phoneArr) ? (last($phoneArr)['phoneNumber'] ?? null) : null;
        if ($phone) {
            $phone = Helper::formatPhone($phone);
        }

        return $phone;
    }

    public function parseExaminerEmail($emailArr)
    {
        return is_array($emailArr) && count($emailArr) ? ($emailArr[0]['email'] ?? null) : null;
    }

    public function parseCompanyLogo($brandedCompanyArr)
    {
        $logo = '';
        $logoHasPublicUrl = false;

        if (is_array($brandedCompanyArr) && ! empty($brandedCompanyArr['company']['logo'])) {
            $logo = $brandedCompanyArr['company']['logo'];
        }

        if (is_array($brandedCompanyArr) && ! empty($brandedCompanyArr['company']['publicLogo'])) {
            $logo = $brandedCompanyArr['company']['publicLogo'];
            $logoHasPublicUrl = true;
        }

        if (! $logo) {
            $holdingCompanyDetail = Helper::getHoldingCompanyDetail();
            $logo = $holdingCompanyDetail['logo'] ?? null;

            if ($holdingCompanyDetail['public_logo']) {
                $logo = $holdingCompanyDetail['public_logo'];
                $logoHasPublicUrl = true;
            }
        }

        if (! $logo) {
            \Log::info('WORKFLOW - failed to fetch logo ', (array) $brandedCompanyArr);
        }

        if ($logoHasPublicUrl) {
            return $logo;
        }

        // From gfs-saas-infra/src/Foundation/Helpers.php
        $path = removeS3HostAndBucketFromURL($logo);
        \Log::info('WORKFLOW - S3 path for company logo: ' . $path);

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return \Storage::disk('s3')->temporaryUrl($path, Carbon::now()->addMinutes(4320));
    }

    public function parseCompanyName($brandedCompanyArr)
    {
        if (is_array($brandedCompanyArr) && ! empty($brandedCompanyArr['company']['companyName'])) {
            return $brandedCompanyArr['company']['companyName'];
        }

        return null;
    }

    public function getInsuredPortalUrl()
    {
        return Helper::createPortalURL('InsuredPortal');
    }

    public function parseReferenceNo($referenceNo)
    {
        $holdingCompanyDetail = Helper::getHoldingCompanyDetail();
        $tenant = getTenant();

        return sprintf('%s%s%s', ucfirst(substr($tenant, 0, 1)), $holdingCompanyDetail['naic_number'], $referenceNo);
    }
}
