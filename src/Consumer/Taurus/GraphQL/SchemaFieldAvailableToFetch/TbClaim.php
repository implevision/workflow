<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

class TbClaim
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
        $this->queryName = 'claim';
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
            'ClaimId' => [
                'GraphQLschemaToReplace' => [
                    'claimId' => null,
                ],
                'jqFilter' => '.claim.ClaimId',
            ],
            'PolicyNumber' => [
                'GraphQLschemaToReplace' => [
                    'riskId' => null,
                ],
                'jqFilter' => '.claim.riskId',
            ],
            'PolicyId' => [
                'GraphQLschemaToReplace' => [
                    'policyId' => null,
                ],
                'jqFilter' => '.claim.policyId',
            ],
            'DateOfLoss' => [
                'GraphQLschemaToReplace' => [
                    'dateOfLoss' => null,
                ],
                'jqFilter' => '.claim.dateOfLoss',
                'parseResultCallback' => 'formatDate',
            ],
            'InsuredName' => [
                'GraphQLschemaToReplace' => [
                    'insuredName' => null,
                ],
                'jqFilter' => '.claim.insuredName',
            ],
            'ClaimantEmail' => [
                'GraphQLschemaToReplace' => [
                    'claimCommunication' => [
                        'isAcceptEmail' => null,
                        'primaryEmail' => null,
                        'secondaryEmail' => null,
                    ],
                ],
                'jqFilter' => '.claim.claimCommunication',
                'parseResultCallback' => 'parseClaimCommunication',
            ],
        ];

        $fieldMapping['InsuredPropertyAddress'] = [
            'GraphQLschemaToReplace' => [
                'insuredPerson' => [
                    'TbPersonaddress' => [
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
                    ],
                ],
            ],
            'jqFilter' => '.claim.insuredPerson.TbPersonaddress[] | select(.isDefaultAddress == "Y" and .addressTypeCode == "Location")',
            'parseResultCallback' => 'parsePropertyAddress',
        ];

        $fieldMapping['InsuredMailingAddress'] = [
            'GraphQLschemaToReplace' => $fieldMapping['InsuredPropertyAddress']['GraphQLschemaToReplace'],
            'jqFilter' => '.claim.insuredPerson.TbPersonaddress[] | select(.addressTypeCode == "Mailing")',
            'parseResultCallback' => 'parseMailingAddress',
        ];

        $fieldMapping['AdjustingFirmName'] = [
            'GraphQLschemaToReplace' => [
                'adjustingFirm' => [
                    'personInfo' => [
                        'fullName' => null,
                    ],
                ],
            ],
            'jqFilter' => '[.claim.adjustingFirm[].personInfo.fullName]',
            'parseResultCallback' => 'parseAdjustingFirmName',
        ];

        $fieldMapping['AdjustingFirmAddress'] = [
            'GraphQLschemaToReplace' => [
                'adjustingFirm' => [
                    'personInfo' => [
                        'TbPersonaddress' => [
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
                        ],
                    ],
                ],
            ],
            'jqFilter' => '[.claim.adjustingFirm[].personInfo.TbPersonaddress[] | select(.addressTypeCode == "Mailing")]',
            'parseResultCallback' => 'parseAdjustingFirmAddress',
        ];

        $fieldMapping['AdjustingFirmEmail'] = [
            'GraphQLschemaToReplace' => [
                'adjustingFirm' => [
                    'personInfo' => [
                        'emailInfo' => [
                            'email' => null,
                            'isDefault' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '[.claim.adjustingFirm[].personInfo.emailInfo[0] | select(.isDefault == "Y")]',
            'parseResultCallback' => 'parseAdjustingFirmEmail',
        ];

        $fieldMapping['AdjustingFirmPhone'] = [
            'GraphQLschemaToReplace' => [
                'adjustingFirm' => [
                    'personInfo' => [
                        'phoneInfo' => [
                            'phoneNumber' => null,
                            'isDefault' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '[.claim.adjustingFirm[].personInfo.phoneInfo[0] | select(.isDefault == "Y")]',
            'parseResultCallback' => 'parseAdjustingFirmPhone',
        ];

        $fieldMapping['ExaminerName'] = [
            'GraphQLschemaToReplace' => [
                'serviceRepresentative' => [
                    'screenName' => null,
                ],
            ],
            'jqFilter' => '.claim.serviceRepresentative.screenName',
        ];

        $fieldMapping['ExaminerEmail'] = [
            'GraphQLschemaToReplace' => [
                'serviceRepresentative' => [
                    'TbPersonInfo' => [
                        'emailInfo' => [
                            'email' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '[.claim.serviceRepresentative.TbPersonInfo.emailInfo[]]',
            'parseResultCallback' => 'parseExaminerEmail',
        ];

        $fieldMapping['CompanyLogo'] = [
            'GraphQLschemaToReplace' => [
                'agency' => [
                    'brandedCompany' => [
                        'company' => [
                            'logo' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.claim.agency.brandedCompany[]',
            'parseResultCallback' => 'parseCompanyLogo',
        ];

        $fieldMapping['WYOCompanyName'] = [
            'GraphQLschemaToReplace' => [
                'agency' => [
                    'brandedCompany' => [
                        'company' => [
                            'companyName' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.claim.agency.brandedCompany[]',
            'parseResultCallback' => 'parseCompanyName',
        ];

        $fieldMapping['InsuredPortal'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getInsuredPortalUrl',
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
            'addressLine1' => ($addressArr['houseNo'] ?? '').' '.($addressArr['streetName'] ?? ($addressArr['addressLine1'] ?? '')),
            'city' => $addressArr['tbCity']['name'] ?? null,
            'city' => $addressArr['tbCounty']['name'] ?? null,
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
        return is_array($phoneArr) && count($phoneArr) ? (last($phoneArr)['phoneNumber'] ?? null) : null;
    }

    public function parseClaimCommunication($claimCommunication)
    {
        if (empty($claimCommunication)) {
            return null;
        }

        $email = $claimCommunication['primaryEmail'] ?? null;
        if (empty($email)) {
            $email = $claimCommunication['secondaryEmail'] ?? null;
        }

        return $email;
    }

    public function parseExaminerEmail($emailArr)
    {
        return is_array($emailArr) && count($emailArr) ? ($emailArr[0]['email'] ?? null) : null;
    }

    public function parseCompanyLogo($brandedCompanyArr)
    {
        if (is_array($brandedCompanyArr) && ! empty($brandedCompanyArr['company']['logo'])) {
            $logo = $brandedCompanyArr['company']['logo'];
        }

        $holdingCompanyDetail = Helper::getHoldingCompanyDetail();
        $logo = $holdingCompanyDetail['logo'] ?? null;

        // From gfs-saas-infra/src/Foundation/Helpers.php
        return removeS3HostAndBucketFromURL($logo);
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
}
