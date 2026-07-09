<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Avatar\Infrastructure\Models\Api\v1\TbPolicy;
use Avatar\Infrastructure\Models\Api\v1\TbProduct;
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
                'jqFilter' => '.claimQuery.claimId',
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
                'jqFilter' => '.claimQuery.updatedAt',
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

        $fieldMapping['ClaimCreatedDate'] = [
            'GraphQLschemaToReplace' => [
                'createdAt' => null,
            ],
            'jqFilter' => '.claimQuery.createdAt',
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
                    'claimDetailJson' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.newScreenData.temporaryAddress',
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
                'policyId' => null,
            ],
            'jqFilter' => '.claimQuery',
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
                'policyId' => null,
            ],
            'jqFilter' => '.claimQuery',
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

        $fieldMapping['ClaimantTemporaryPhone'] = [
            'GraphQLschemaToReplace' => [
                'claimCommunication' => [
                    'temporaryPhone' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.claimCommunication.temporaryPhone',
        ];

        $fieldMapping['ClaimantSecondaryPhone'] = [
            'GraphQLschemaToReplace' => [
                'claimCommunication' => [
                    'secondaryPhone' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.claimCommunication.secondaryPhone',
        ];

        $fieldMapping['ClaimantTemporaryEmail'] = [
            'GraphQLschemaToReplace' => [
                'claimCommunication' => [
                    'temporaryEmail' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.claimCommunication.temporaryEmail',
        ];

        $fieldMapping['ClaimantSecondaryEmail'] = [
            'GraphQLschemaToReplace' => [
                'claimCommunication' => [
                    'secondaryEmail' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.claimCommunication.secondaryEmail',
        ];

        $fieldMapping['InsuredPortal'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getInsuredPortalUrl',
        ];


        $fieldMapping['WaiverReceiptDate'] = [
            'GraphQLschemaToReplace' => [
                'addInfo' => [
                    'claimDetailJson' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.waiverRecieptDate',
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
                    'claimDetailJson' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.insuredContactDate',
            'parseResultCallback' => 'formatDate',
        ];

        $fieldMapping['LossInspectedDate'] = [
            'GraphQLschemaToReplace' => [
                'addInfo' => [
                    'claimDetailJson' => null,
                ],
            ],
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.claimLossCorrectionA.claimReportingDTO.lossInspectedDate',
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

        $statusLogSchema = [
            'statusLog' => [
                'tranTypeCode' => null,
                'tranSubTypeCode' => null,
                'insertedDate' => null,
            ],
        ];

        $fieldMapping['RapLatestStatus'] = [
            'GraphQLschemaToReplace' => $statusLogSchema,
            'jqFilter' => '[.claimQuery.statusLog[] | select(.tranTypeCode == "Rap_Open" or .tranTypeCode == "Rap_Close")] | sort_by(.insertedDate) | reverse | .[0].tranSubTypeCode',
        ];

        $fieldMapping['IccLatestStatus'] = [
            'GraphQLschemaToReplace' => $statusLogSchema,
            'jqFilter' => '[.claimQuery.statusLog[] | select(.tranTypeCode == "Icc_Open" or .tranTypeCode == "Icc_Close")] | sort_by(.insertedDate) | reverse | .[0].tranSubTypeCode',
        ];

        $claimAdditionalSchema = [
            'addInfo' => [
                'claimDetailJson' => null,
            ],
        ];

        $fieldMapping['ClaimGCOF'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.newScreenData.gcof',
        ];


        $fieldMapping['ClaimWaterLineFlipExt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.newScreenData.waterLineFlipExt',
        ];

        $fieldMapping['ClaimWaterLineFlipInt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.newScreenData.waterLineFlipInt',
        ];

        $fieldMapping['ClaimAppWaterLineFlipExt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.newScreenData.appWaterLineFlipExt',
        ];

        $fieldMapping['ClaimAppWaterLineFlipInt'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.newScreenData.appWaterLineFlipInt',
        ];

        $fieldMapping['ClaimInspectionMethod'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.newScreenData.inspectionMethod',
        ];

        $fieldMapping['ClaimDelayReason'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.newScreenData.delayReason',
        ];

        $fieldMapping['ClaimHoursInFlood'] = [
            'GraphQLschemaToReplace' => $claimAdditionalSchema,
            'jqFilter' => '.claimQuery.addInfo.claimDetailJson.newScreenData.hoursInFlood',
        ];

        $paymentTotalsSchema = [
            'paymentTotals' => null,
        ];

        $fieldMapping['BuildingAdvancedPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.building_advanced_payment_amount',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['BuildingFinalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.building_final_payment_amount',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['BuildingSupplementalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.building_supplemental_payment_amount',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['BuildingTotalPayments'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.total_building_payments',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['ContentsAdvancedPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.contents_advanced_payment_amount',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['ContentsFinalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.contents_final_payment_amount',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['ContentsSupplementalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.contents_supplemental_payment_amount',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['ContentsTotalPayments'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.total_contents_payments',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['ICCAdvancedPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.icc_advanced_payment_amount',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['ICCFinalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.icc_final_payment_amount',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['ICCSupplementalPayment'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.icc_supplemental_payment_amount',
            'parseResultCallback' => 'formatCurrency',
        ];

        $fieldMapping['ICCTotalPayments'] = [
            'GraphQLschemaToReplace' => $paymentTotalsSchema,
            'jqFilter' => '.claimQuery.paymentTotals.total_icc_payments',
            'parseResultCallback' => 'formatCurrency',
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

        $fieldMapping['AdjusterEmail'] = [
            'GraphQLschemaToReplace' => [
                'adjuster' => [
                    'TbPersonInfo' => [
                        'emailInfo' => [
                            'email' => null,
                            'isDefault' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '[.claimQuery.adjuster.TbPersonInfo.emailInfo[0] | select(.isDefault == "Y")]',
            'parseResultCallback' => 'parseAdjustingFirmEmail',
        ];
    
        $fieldMapping['AttachClaimAssignmentForm'] = [
            'GraphQLschemaToReplace' => [
                'docuploadinfo' => [
                    'uploadDate' => null,
                    'doctypes' => [
                        'docTypeCode' => null,
                    ],
                    'docUploadDocInfoRel' => [
                        'docUploadReference' => [
                            'tableRefId' => null,
                        ],
                        'docInfo' => [
                            'docPath' => null,
                            'docName' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => '
                [
                    .claimQuery.docuploadinfo[]
                    | select(.doctypes.docTypeCode == "ASSIGNMENTS")
                    | .uploadDate as $uploadDate
                    | .docUploadDocInfoRel[]
                    | .docUploadReference.tableRefId as $tableRefId
                    | .docInfo[]
                    | {
                        name: .docName,
                        path: .docPath,
                        tableRefId: $tableRefId,
                        uploadDate: $uploadDate
                      }
                ] | sort_by(.uploadDate) | reverse | .[0:1]
            ',
            'parseResultCallback' => 'generatePresignedUrl',
        ];

        $fieldMapping['AdjusterName'] = [
            'GraphQLschemaToReplace' => [
                'adjuster' => [
                    'screenName' => null,
                ],
            ],
            'jqFilter' => '.claim.adjuster.screenName',
        ];

        $fieldMapping['CurrentYear'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getCurrentYear',
        ];

        $fieldMapping['HoldingCompanyName'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getHoldingCompanyName',
        ];

        $fieldMapping['HoldingCompanyPhoneNumber'] = [
            'GraphQLschemaToReplace' => [],
            'jqFilter' => '',
            'parseResultCallback' => 'getHoldingCompanyPhoneNumber',
        ];

        $deductibles = [
            'transaction' => [
                'coverageDetails' => [
                    'coverageSchedules' => [
                        'policyCoverageMaster' => [
                            'policyCoverageCoverages' => [
                                'coverageCode' => null,
                            ],
                        ],
                    ],
                    'insuredCoverageValue' => null,
                    'prDiscountCode' => null,
                ],
            ],
        ];

        $fieldMapping['BuildingCoverageDeductibleAmount'] = [
            'GraphQLschemaToReplace' => $deductibles,
            'jqFilter' => '.claim.transaction.coverageDetails',
            'parseResultCallback' => 'parseBuildingDeductibles',
        ];

        $fieldMapping['ContentsCoverageDeductibleAmount'] = [
            'GraphQLschemaToReplace' => $deductibles,
            'jqFilter' => '.claim.transaction.coverageDetails',
            'parseResultCallback' => 'parseContentsDeductibles',
        ];

        $advancePayment = [
            'claimReserve' => [
                'tranTypeCode' => null,
                'tranSubTypeCode' => null,
                'amount' => null,
                'claimReserveDetail' => [
                    'reserveType' => null,
                ],
            ],
        ];

        $fieldMapping['BuildingAdvancePayment'] = [
            'GraphQLschemaToReplace' => $advancePayment,
            'jqFilter' => '.claim.claimReserve',
            'parseResultCallback' => 'parseBuildingAdvancePayment',
        ];

        $fieldMapping['ContentAdvancePayment'] = [
            'GraphQLschemaToReplace' => $advancePayment,
            'jqFilter' => '.claim.claimReserve',
            'parseResultCallback' => 'parseContentAdvancePayment',
        ];

        $payment = [
            'claimReserve' => [
                'tranTypeCode' => null,
                'tranSubTypeCode' => null,
                'claimCoverageTrans' => [
                    'coverageCode' => null,
                    'amount' => null,
                    'tbCvgpccoverage' => [
                        'coverageCode' => null,
                    ],
                ],
            ],
        ];

        $fieldMapping['BuildingPayment'] = [
            'GraphQLschemaToReplace' => $payment,
            'jqFilter' => '.claim.claimReserve',
            'parseResultCallback' => 'parseBuildingPayment',
        ];

        $fieldMapping['ContentPayment'] = [
            'GraphQLschemaToReplace' => $payment,
            'jqFilter' => '.claim.claimReserve',
            'parseResultCallback' => 'parseContentPayment',
        ];

        return $fieldMapping;
    }

    public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }

    public function formatCurrency($amount)
    {
        return Helper::formatCurrency($amount);
    }


    private function parseAddress($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $address = [
            'addressLine1' => ($addressArr['houseNo'] ?? '').' '.($addressArr['streetName'] ?? ($addressArr['addressLine1'] ?? '')),
            'addressLine2' => $addressArr['addressLine2'] ?? '',
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

    public function resolveCompanyLogoUrl($response)
    {
        [$brandedCompanyArr, $policyId] = $this->extractClaimContext($response);

        return Helper::parseCompanyLogo($brandedCompanyArr, $policyId);
    }

    public function parseCompanyName($response)
    {
        [$brandedCompanyArr, $policyId] = $this->extractClaimContext($response);

        $companyName = $brandedCompanyArr['company']['companyName'] ?? null;
        if (! empty($companyName)) {
            return $companyName;
        }

        $policyData = TbPolicy::find($policyId);
        $holdingCompanyId = TbProduct::find($policyData?->n_ProductId_FK)?->holding_company_id;

        $holdingCompanyDetail = Helper::getHoldingCompanyDetail($holdingCompanyId);
        if (! empty($holdingCompanyDetail['wyo'])) {
            return $holdingCompanyDetail['wyo'];
        }

        return Helper::getHoldingCompanyDetail()['wyo'] ?? '';
    }

    private function extractClaimContext($response): array
    {
        $response = is_array($response) ? $response : [];
        $brandedCompany = $response['agency']['brandedCompany'] ?? [];

        if (is_array($brandedCompany) && array_key_exists('company', $brandedCompany)) {
            $normalizedBrandedCompany = $brandedCompany;
        } else {
            $normalizedBrandedCompany = is_array($brandedCompany) ? ($brandedCompany[0] ?? []) : [];
        }

        return [
            $normalizedBrandedCompany,
            $response['policyId'] ?? null,
        ];
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
    public static function formatFileName(?string $fileName): string
    {
        if (empty($fileName)) {
            return '';
        }

        return pathinfo($fileName, PATHINFO_FILENAME);
    }

    public function generatePresignedUrl(array $documents): array
    {
        $data = array_values(array_map(
            function ($doc) {
                return [
                    'name' => $this->formatFileName($doc['name']),
                    'path' => Helper::generatePresignedUrl($doc['path']),
                ];
            },
            $documents
        ));

        return $data;
    }

    public function getCurrentYear()
    {
        return date('Y');
    }

    public function getHoldingCompanyName()
    {
        $holdingCompanyDetail = Helper::getHoldingCompanyDetail();

        return $holdingCompanyDetail['wyo'] ?? '';
    }

    public function getHoldingCompanyPhoneNumber()
    {
        $holdingCompanyDetail = Helper::getHoldingCompanyDetail();

        $phoneNumber = $holdingCompanyDetail['company_phone'] ?? '';

        return Helper::formatPhone($phoneNumber);
    }

    public function parseBuildingDeductibles($coverageDetails)
    {
        $buildingDeductibles = array_filter($coverageDetails, function ($coverageDetail) {
            return data_get($coverageDetail, 'coverageSchedules.policyCoverageMaster.policyCoverageCoverages.coverageCode') === 'FLDBLDCVGAMT';
        });

        $buildingDeductibleValue = ! empty($buildingDeductibles) ? (reset($buildingDeductibles)['prDiscountCode'] ?? null) : null;

        if ($buildingDeductibleValue) {
            preg_match('/(\d+(?:\.\d+)?)$/', $buildingDeductibleValue, $matches);
            $buildingDeductibleValue = $matches[1] ?? $buildingDeductibleValue;
        }

        return $buildingDeductibleValue ? Helper::formatCurrency($buildingDeductibleValue) : Helper::formatCurrency(0);
    }

    public function parseContentsDeductibles($coverageDetails)
    {
        $contentsDeductibles = array_filter($coverageDetails, function ($coverageDetail) {
            return data_get($coverageDetail, 'coverageSchedules.policyCoverageMaster.policyCoverageCoverages.coverageCode') === 'FLDCONTCVGAMT';
        });

        $contentsDeductibleValue = ! empty($contentsDeductibles) ? (reset($contentsDeductibles)['prDiscountCode'] ?? null) : null;

        if ($contentsDeductibleValue) {
            preg_match('/(\d+(?:\.\d+)?)$/', $contentsDeductibleValue, $matches);
            $contentsDeductibleValue = $matches[1] ?? $contentsDeductibleValue;
        }

        return $contentsDeductibleValue ? Helper::formatCurrency($contentsDeductibleValue) : Helper::formatCurrency(0);
    }

    public function parseBuildingAdvancePayment($claimReserves)
    {
        $buildingAdvancePayments = array_filter($claimReserves, function ($reserve) {
            return data_get($reserve, 'tranTypeCode') === 'Loss Payment' && data_get($reserve, 'claimReserveDetail.reserveType') === 'A';
        });

        $amount = 0;

        foreach ($buildingAdvancePayments as $payment) {
            if (data_get($payment, 'tranSubTypeCode') === 'BUILDCLAIMPAYMENT') {
                $amount += data_get($payment, 'amount', 0);
            }
        }

        return Helper::formatCurrency(abs($amount ?? 0));
    }

    public function parseContentAdvancePayment($claimReserves)
    {
        $contentsAdvancePayments = array_filter($claimReserves, function ($reserve) {
            return data_get($reserve, 'tranTypeCode') === 'Loss Payment' && data_get($reserve, 'claimReserveDetail.reserveType') === 'A';
        });

        $amount = 0;

        foreach ($contentsAdvancePayments as $payment) {
            if (data_get($payment, 'tranSubTypeCode') === 'CONTCLAIMPAYMENT') {
                $amount += data_get($payment, 'amount', 0);
            }
        }

        return Helper::formatCurrency(abs($amount ?? 0));
    }

    public function parseBuildingPayment($coverageDetails)
    {
        $buildingPayments = array_filter($coverageDetails, function ($coverageDetail) {
            return data_get($coverageDetail, 'tranTypeCode') === 'Loss Payment' &&
                data_get($coverageDetail, 'claimCoverageTrans.tbCvgpccoverage.coverageCode') === 'FLDBLDCVGAMT';
        });

        $amount = 0;

        foreach ($buildingPayments as $payment) {
            $amount += data_get($payment, 'claimCoverageTrans.amount', 0);
        }

        return Helper::formatCurrency(abs($amount) ?? 0);
    }

    public function parseContentPayment($coverageDetails)
    {
        $contentsPayments = array_filter($coverageDetails, function ($coverageDetail) {
            return data_get($coverageDetail, 'tranTypeCode') === 'Loss Payment' &&
                data_get($coverageDetail, 'claimCoverageTrans.tbCvgpccoverage.coverageCode') === 'FLDCONTCVGAMT';
        });

        $amount = 0;

        foreach ($contentsPayments as $payment) {
            $amount += data_get($payment, 'claimCoverageTrans.amount', 0);
        }

        return Helper::formatCurrency(abs($amount) ?? 0);
    }
}
