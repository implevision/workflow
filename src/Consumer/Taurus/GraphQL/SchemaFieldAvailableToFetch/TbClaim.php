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
                    'policyNumber' => null,
                ],
                'jqFilter' => '.claim.policyNumber',
            ],
            'ReferenceNo' => [
                'GraphQLschemaToReplace' => [
                    'referenceNo' => null,
                ],
                'jqFilter' => '.claim.referenceNo',
                'parseResultCallback' => 'parseReferenceNo',
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

            'UpdatedByDate' => [
                'GraphQLschemaToReplace' => [
                    'updatedDate' => null,
                ],
                'jqFilter' => '.claim.updatedDate',
                'parseResultCallback' => 'formatDate',
            ],

            'PolicyId' => [
                'GraphQLschemaToReplace' => [
                    'policyId' => null,
                ],
                'jqFilter' => '.claim.policyId',
            ],

        ];

        $fieldMapping['UpdatedByName'] = [
            'GraphQLschemaToReplace' => [
                'updatedBy' => [
                    'screenName' => null,
                ],
            ],
            'jqFilter' => '.claim.updatedBy.screenName',
        ];

        $fieldMapping['ClaimEnteredByName'] = [
            'GraphQLschemaToReplace' => [
                'claimEnteredBy' => [
                    'screenName' => null,
                ],
            ],
            'jqFilter' => '.claim.claimEnteredBy.screenName',
        ];

        $fieldMapping['DateAllocated'] = [
            'GraphQLschemaToReplace' => [
                'dateAllocated' => null,
            ],
            'jqFilter' => '.claim.dateAllocated',
            'parseResultCallback' => 'parseDateAllocated',
        ];

        $fieldMapping['ClaimEnteredByCreatedDate'] = [
            'GraphQLschemaToReplace' => [
                'claimCommunication' => [
                    'createdDate' => null,
                ],
            ],
            'jqFilter' => '.claim.claimCommunication.createdDate',
            'parseResultCallback' => 'formatDate',
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

        $fieldMapping['TemporaryAddress'] = [
            'GraphQLschemaToReplace' => [
                'addInfo' => [
                    'claim_detail_json' => [
                        'newScreenData' => [
                            'temporaryAddress' => [
                                'addressLine1' => null,
                                'cityName' => null,
                                'stateCode' => null,
                                'stateName' => null,
                                'zipCode' => null,
                                'zipCodeSuffix' => null,
                                'countyName' => null,
                                'isForeignAddress' => null,
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.temporaryAddress',
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
                            'publicLogo' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.claim.agency.brandedCompany[]',
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
            'jqFilter' => '.claim.agency.brandedCompany[]',
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
            'jqFilter' => '.claim.claimCommunication',
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
                    'claim_detail_json' => [
                        'waiverRecieptDate' => null,
                    ],
                ],
            ],
            'jqFilter' => '.claim.addInfo.claim_detail_json.waiverRecieptDate',
            'parseResultCallback' => 'formatDate',
        ];

        $fieldMapping['ClaimStatusCode'] = [
            'GraphQLschemaToReplace' => [
                'claimStatusCode' => null,
            ],
            'jqFilter' => '.claim.claimStatusCode',
        ];

        $fieldMapping['DateContacted'] = [
            'GraphQLschemaToReplace' => [
                'addInfo' => [
                    'claim_detail_json' => [
                        'insuredContactDate' => null,
                    ],
                ],
            ],
            'jqFilter' => '.claim.addInfo.claim_detail_json.insuredContactDate',
            'parseResultCallback' => 'formatDate',
        ];

        $fieldMapping['LossInspectedDate'] = [
            'GraphQLschemaToReplace' => [
                'addInfo' => [
                    'claim_detail_json' => [
                        'claimLossCorrectionA' => [
                            'claimReportingDTO' => [
                                'lossInspectedDate' => null,
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => '.claim.addInfo.claim_detail_json.claimLossCorrectionA.claimReportingDTO.lossInspectedDate',
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
            'jqFilter' => '.claim.allocatedTo.screenName',
        ];

        $fieldMapping['AllocatedToFCN'] = [
            'GraphQLschemaToReplace' => $allocatedToSchema,
            'jqFilter' => '.claim.allocatedTo.fcn',
        ];

        $fieldMapping['RapLatestStatusText'] = [
            'GraphQLschemaToReplace' => [
                'rapLatestStatusText' => null,
            ],
            'jqFilter' => '.claim.rapLatestStatusText',
        ];

        $claimAdditionalSchema = [
            'addInfo' => [
                'claim_detail_json' => [
                    'iccfee'            => null,
                    'waiverRecieptDate' => null,
                    'waiverRequired'    => null,
                    'newScreenData' => [
                        'gcof'                => null,
                        'waterLineFlipExt'    => null,
                        'waterLineFlipInt'    => null,
                        'appWaterLineFlipExt' => null,
                        'appWaterLineFlipInt' => null,
                        'inspectionMethod'    => null,
                        'delayReason'         => null,
                        'hoursInFlood'        => null,
                    ],
                ],
            ],
        ];

        $fieldMapping['ClaimGCOF'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.gcof',
        ];

        $fieldMapping['ClaimWaterLineFlipExt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.waterLineFlipExt',
        ];

        $fieldMapping['ClaimWaterLineFlipInt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.waterLineFlipInt',
        ];

        $fieldMapping['ClaimAppWaterLineFlipExt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.appWaterLineFlipExt',
        ];

        $fieldMapping['ClaimAppWaterLineFlipInt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.appWaterLineFlipInt',
        ];

        $fieldMapping['ClaimInspectionMethod'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.inspectionMethod',
        ];

        $fieldMapping['ClaimDelayReason'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.delayReason',
        ];

        $fieldMapping['ClaimHoursInFlood'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claim.addInfo.claim_detail_json.newScreenData.hoursInFlood',
        ];

        $paymentTotalsSchema = [
            'paymentTotals' => [
                'building_advanced_payment_amount' => null,
                'building_final_payment_amount' => null,
                'building_supplemental_payment_amount' => null,
                'total_building_payments' => null,
                'contents_advanced_payment_amount' => null,
                'contents_final_payment_amount' => null,
                'contents_supplemental_payment_amount' => null,
                'total_contents_payments' => null,
                'icc_advanced_payment_amount' => null,
                'icc_final_payment_amount' => null,
                'icc_supplemental_payment_amount' => null,
                'total_icc_payments' => null,
            ],
        ];

        $fieldMapping['BuildingAdvancedPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.building_advanced_payment_amount',
        ];

        $fieldMapping['BuildingFinalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.building_final_payment_amount',
        ];

        $fieldMapping['BuildingSupplementalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.building_supplemental_payment_amount',
        ];

        $fieldMapping['BuildingTotalPayments'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.total_building_payments',
        ];

        $fieldMapping['ContentsAdvancedPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.contents_advanced_payment_amount',
        ];

        $fieldMapping['ContentsFinalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.contents_final_payment_amount',
        ];

        $fieldMapping['ContentsSupplementalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.contents_supplemental_payment_amount',
        ];

        $fieldMapping['ContentsTotalPayments'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.total_contents_payments',
        ];

        $fieldMapping['ICCAdvancedPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.icc_advanced_payment_amount',
        ];

        $fieldMapping['ICCFinalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.icc_final_payment_amount',
        ];

        $fieldMapping['ICCSupplementalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.icc_supplemental_payment_amount',
        ];

        $fieldMapping['ICCTotalPayments'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claim.paymentTotals.total_icc_payments',
        ];

        $fieldMapping['CauseOfLoss'] = [
            'GraphQLschemaToReplace' => [
                'causeOfLoss' => null,
            ],
            'jqFilter' => '.claim.causeOfLoss',
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
            'jqFilter' => '.claim.agency.personUniqueId',
        ];

        $fieldMapping['AgencyInsuredPersonInfoId'] = [
            'GraphQLschemaToReplace' => $agencySchema,
            'jqFilter' => '.claim.agency.insuredPersonInfoId',
        ];

        $fieldMapping['AgencyFullName'] = [
            'GraphQLschemaToReplace' => $agencySchema,
            'jqFilter' => '.claim.agency.fullName',
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
