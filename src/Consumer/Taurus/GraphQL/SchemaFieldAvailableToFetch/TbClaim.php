<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

class TbClaim extends AbstractSchema
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
        $this->queryName = 'claimQuery';
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
                'jqFilter' => '.claimQuery.ClaimId',
            ],
            'PolicyNumber' => [
                'GraphQLschemaToReplace' => [
                    'policyNumber' => null,
                ],
                'jqFilter' => '.claimQuery.policyNumber',
            ],
            'ReferenceNo' => [
                'GraphQLschemaToReplace' => [
                    'referenceNo' => null,
                ],
                'jqFilter' => '.claimQuery.referenceNo',
                'parseResultCallback' => 'parseReferenceNo',
            ],

            'DateOfLoss' => [
                'GraphQLschemaToReplace' => [
                    'dateOfLoss' => null,
                ],
                'jqFilter' => '.claimQuery.dateOfLoss',
                'parseResultCallback' => 'formatDate',
            ],
            'InsuredName' => [
                'GraphQLschemaToReplace' => [
                    'insuredName' => null,
                ],
                'jqFilter' => '.claimQuery.insuredName',
            ],

            'UpdatedByDate' => [
                'GraphQLschemaToReplace' => [
                    'updatedAt' => null,
                ],
                'jqFilter' => '.claim.updatedAt',
                'parseResultCallback' => 'formatDate',
            ],

            'PolicyId' => [
                'GraphQLschemaToReplace' => [
                    'policyId' => null,
                ],
                'jqFilter' => '.claimQuery.policyId',
            ],

        ];

        $fieldMapping['UpdatedByName'] = [
            'GraphQLschemaToReplace' => [
                'updatedBy' => [
                    'screenName' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.updatedBy.screenName',
        ];

        $fieldMapping['ClaimEnteredByName'] = [
            'GraphQLschemaToReplace' => [
                'claimEnteredBy' => [
                    'screenName' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.claimEnteredBy.screenName',
        ];

        $fieldMapping['DateAllocated'] = [
            'GraphQLschemaToReplace' => [
                'dateAllocated' => null,
            ],
            'jqFilter' => '.claimQuery.dateAllocated',
            'parseResultCallback' => 'parseDateAllocated',
        ];

        $fieldMapping['ClaimEnteredByCreatedDate'] = [
            'GraphQLschemaToReplace' => [
                'claimCommunication' => [
                    'createdDate' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.claimCommunication.createdDate',
            'parseResultCallback' => 'formatDate',
        ];

        $fieldMapping['InsuredPhone'] = [
            'GraphQLschemaToReplace' => [
                'insuredPerson' => [
                    'phoneInfo' => [
                        'phoneNumber' => null,
                        'isDefault'   => null,
                    ],
                ],
            ],
            'jqFilter' => '.claimQuery.insuredPerson.phoneInfo[0].phoneNumber',
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
            'jqFilter' => '.claimQuery.insuredPerson.TbPersonaddress[] | select(.isDefaultAddress == "Y" and .addressTypeCode == "Location")',
            'parseResultCallback' => 'parsePropertyAddress',
        ];

        $fieldMapping['InsuredMailingAddress'] = [
            'GraphQLschemaToReplace' => $fieldMapping['InsuredPropertyAddress']['GraphQLschemaToReplace'],
            'jqFilter' => '.claimQuery.insuredPerson.TbPersonaddress[] | select(.addressTypeCode == "Mailing")',
            'parseResultCallback' => 'parseMailingAddress',
        ];

        $fieldMapping['TemporaryAddress'] = [
            'GraphQLschemaToReplace' => [
                'addInfo' => [
                    'claim_detail_json' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.newScreenData.temporaryAddress',
            'parseResultCallback' => 'parseTemporaryAddress',
        ];

        $fieldMapping['AdjustingFirmName'] = [
            'GraphQLschemaToReplace' => [
                'adjustingFirm' => [
                    'personInfo' => [
                        'fullName' => null,
                    ],
                ],
            ],
            'jqFilter' => '[.claimQuery.adjustingFirm[].personInfo.fullName]',
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
            'jqFilter' => '[.claimQuery.adjustingFirm[].personInfo.TbPersonaddress[] | select(.addressTypeCode == "Mailing")]',
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
            'jqFilter' => '[.claimQuery.adjustingFirm[].personInfo.emailInfo[0] | select(.isDefault == "Y")]',
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
            'jqFilter' => '[.claimQuery.adjustingFirm[].personInfo.phoneInfo[0] | select(.isDefault == "Y")]',
            'parseResultCallback' => 'parseAdjustingFirmPhone',
        ];

        $fieldMapping['ExaminerName'] = [
            'GraphQLschemaToReplace' => [
                'serviceRepresentative' => [
                    'screenName' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.serviceRepresentative.screenName',
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
            'jqFilter' => '[.claimQuery.serviceRepresentative.TbPersonInfo.emailInfo[]]',
            'parseResultCallback' => 'parseExaminerEmail',
        ];

        $fieldMapping['CompanyLogo'] = [
            'GraphQLschemaToReplace' => [
                'agency' => [
                    'brandedCompany' => [
                        'company' => [
                            'logo' => null,
                            'publicLogo' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.claimQuery.agency.brandedCompany[]',
            'parseResultCallback' => 'resolveCompanyLogoUrl',
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
            'jqFilter' => '.claimQuery.agency.brandedCompany[]',
            'parseResultCallback' => 'parseCompanyName',
        ];

        $fieldMapping['ClaimCommunication'] = [
            'GraphQLschemaToReplace' => [
                'claimCommunication' => [
                    'addressLine1' => null,
                    'addressLine2' => null,
                    'addressLine3' => null,
                    'postalCode' => null,
                    'postalCodeSuffix' => null,
                    'city' => ['name' => null],
                    'state' => ['name' => null],
                    'country' => ['name' => null],
                ],
            ],
            'jqFilter' => '.claimQuery.claimCommunication',
            'parseResultCallback' => 'parseClaimCommunication',
        ];

        $fieldMapping['InsuredPortal'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getInsuredPortalUrl',
        ];


        $fieldMapping['WaiverReceiptDate'] = [
            'GraphQLschemaToReplace' => [
                'addInfo' => [
                    'claim_detail_json' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.waiverRecieptDate',
            'parseResultCallback' => 'formatDate',
        ];

        $fieldMapping['ClaimStatusCode'] = [
            'GraphQLschemaToReplace' => [
                'claimStatusCode' => null,
            ],
            'jqFilter' => '.claimQuery.claimStatusCode',
        ];

        $fieldMapping['DateContacted'] = [
            'GraphQLschemaToReplace' => [
                'addInfo' => [
                    'claim_detail_json' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.insuredContactDate',
            'parseResultCallback' => 'formatDate',
        ];

        $fieldMapping['LossInspectedDate'] = [
            'GraphQLschemaToReplace' => [
                'addInfo' => [
                    'claim_detail_json' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.claimLossCorrectionA.claimReportingDTO.lossInspectedDate',
            'parseResultCallback' => 'formatDate',
        ];

        $allocatedToSchema = [
            'allocatedTo' => [
                'screenName' => null,
                'fcn'        => null,
            ],
        ];

        $fieldMapping['AllocatedToScreenName'] = [
            'GraphQLschemaToReplace' => $allocatedToSchema,
            'jqFilter' => '.claimQuery.allocatedTo.screenName',
        ];

        $fieldMapping['AllocatedToFCN'] = [
            'GraphQLschemaToReplace' => $allocatedToSchema,
            'jqFilter' => '.claimQuery.allocatedTo.fcn',
        ];

        $fieldMapping['RapLatestStatusText'] = [
            'GraphQLschemaToReplace' => [
                'rapLatestStatusText' => null,
            ],
            'jqFilter' => '.claimQuery.rapLatestStatusText',
        ];

        $claimAdditionalSchema = [
            'addInfo' => [
                'claim_detail_json' => null,
            ],
        ];

        $fieldMapping['ClaimGCOF'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.newScreenData.gcof',
        ];

        $fieldMapping['ClaimNewGCOF'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.newScreenData.newGcof',
        ];

        $fieldMapping['ClaimWaterLineFlipExt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.newScreenData.waterLineFlipExt',
        ];

        $fieldMapping['ClaimWaterLineFlipInt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.newScreenData.waterLineFlipInt',
        ];

        $fieldMapping['ClaimAppWaterLineFlipExt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.appWaterLineFlipExt',
        ];

        $fieldMapping['ClaimAppWaterLineFlipInt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.newScreenData.appWaterLineFlipInt',
        ];

        $fieldMapping['ClaimInspectionMethod'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.newScreenData.inspectionMethod',
        ];

        $fieldMapping['ClaimDelayReason'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.newScreenData.delayReason',
        ];

        $fieldMapping['ClaimHoursInFlood'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claim_detail_json.newScreenData.hoursInFlood',
        ];

        $paymentTotalsSchema = [
            'paymentTotals' => null,
        ];

        $fieldMapping['BuildingAdvancedPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.building_advanced_payment_amount',
        ];

        $fieldMapping['BuildingFinalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.building_final_payment_amount',
        ];

        $fieldMapping['BuildingSupplementalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.building_supplemental_payment_amount',
        ];

        $fieldMapping['BuildingTotalPayments'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.total_building_payments',
        ];

        $fieldMapping['ContentsAdvancedPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.contents_advanced_payment_amount',
        ];

        $fieldMapping['ContentsFinalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.contents_final_payment_amount',
        ];

        $fieldMapping['ContentsSupplementalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.contents_supplemental_payment_amount',
        ];

        $fieldMapping['ContentsTotalPayments'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.total_contents_payments',
        ];

        $fieldMapping['ICCAdvancedPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.icc_advanced_payment_amount',
        ];

        $fieldMapping['ICCFinalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.icc_final_payment_amount',
        ];

        $fieldMapping['ICCSupplementalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.icc_supplemental_payment_amount',
        ];

        $fieldMapping['ICCTotalPayments'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.total_icc_payments',
        ];

        $fieldMapping['CauseOfLoss'] = [
            'GraphQLschemaToReplace' => [
                'causeOfLoss' => null,
            ],
            'jqFilter' => '.claimQuery.causeOfLoss',
        ];

        $agencySchema = [
            'agency' => [
                'personUniqueId'      => null,
                'insuredPersonInfoId' => null,
                'fullName'            => null,
            ],
        ];

        $fieldMapping['AgencyPersonUniqueId'] = [
            'GraphQLschemaToReplace' => $agencySchema,
            'jqFilter' => '.claimQuery.agency.personUniqueId',
        ];

        $fieldMapping['AgencyInsuredPersonInfoId'] = [
            'GraphQLschemaToReplace' => $agencySchema,
            'jqFilter' => '.claimQuery.agency.insuredPersonInfoId',
        ];

        $fieldMapping['AgencyFullName'] = [
            'GraphQLschemaToReplace' => $agencySchema,
            'jqFilter' => '.claimQuery.agency.fullName',
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

    public function parseClaimCommunication($data)
    {
        if (empty($data)) {
            return null;
        }

        $parts = [];

        $addressParts = [];

        foreach (['addressLine1', 'addressLine2', 'addressLine3'] as $line) {
            if (!empty($data[$line])) {
                $addressParts[] = trim($data[$line]);
            }
        }

        if (!empty($data['city']['name'])) {
            $addressParts[] = $data['city']['name'];
        }

        if (!empty($data['state']['name'])) {
            $addressParts[] = $data['state']['name'];
        }

        if (!empty($data['postalCode'])) {
            $zip = $data['postalCode'];
            if (!empty($data['postalCodeSuffix'])) {
                $zip .= '-' . $data['postalCodeSuffix'];
            }
            $addressParts[] = $zip;
        }

        if (!empty($data['country']['name'])) {
            $addressParts[] = $data['country']['name'];
        }

        if (!empty($addressParts)) {
            $parts[] = 'Address: ' . implode(', ', $addressParts);
        }

        if (empty($parts)) {
            return null;
        }

        return implode(' | ', $parts);
    }

    public function parseExaminerEmail($emailArr)
    {
        return is_array($emailArr) && count($emailArr) ? ($emailArr[0]['email'] ?? null) : null;
    }

    public function resolveCompanyLogoUrl($brandedCompanyArr)
    {
        return Helper::parseCompanyLogo($brandedCompanyArr);
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

    public function parseDateAllocated($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }

    public function parseTemporaryAddress($address)
    {
        if (empty($address)) {
            return null;
        }

        $parts = [];

        if (!empty($address['addressLine1'])) {
            $parts[] = trim($address['addressLine1']);
        }

        if (!empty($address['cityName'])) {
            $parts[] = $address['cityName'];
        }

        if (!empty($address['stateCode'])) {
            $parts[] = $address['stateCode'];
        } elseif (!empty($address['stateName'])) {
            $parts[] = $address['stateName'];
        }

        if (!empty($address['zipCode'])) {
            $zip = $address['zipCode'];

            if (!empty($address['zipCodeSuffix'])) {
                $zip .= '-' . $address['zipCodeSuffix'];
            }

            $parts[] = $zip;
        }

        if (!empty($address['countyName'])) {
            $parts[] = $address['countyName'];
        }

        return implode(', ', $parts);
    }
}
