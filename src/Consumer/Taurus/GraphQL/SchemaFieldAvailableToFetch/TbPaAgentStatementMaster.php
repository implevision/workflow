<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Carbon\Carbon;
use Taurus\Workflow\Consumer\Taurus\Helper;

class TbPaAgentStatementMaster
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
        $this->queryName = 'agentStatementMaster';
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the TbPaAgentStatementMaster.
     *
     * This method returns an associative array that maps the fields
     * of the TbPaAgentStatementMaster to their corresponding values or attributes.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the TbPaAgentStatementMaster.
     *
     * This method returns the name of the GraphQL query that can be used
     * to fetch data related to the TbPaAgentStatementMaster.
     *
     * @return string The name of the GraphQL query for TbPaAgentStatementMaster.
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Initializes the field mapping with GraphQL schema for the TbPaAgentStatementMaster class.
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
            'statementNo' => [
                'GraphQLschemaToReplace' => [
                    'statementNo' => null,
                ],
                'jqFilter' => '.commission.statementNo',
            ],
            'statementDate' => [
                'GraphQLschemaToReplace' => [
                    'statementDate' => null,
                ],
                'jqFilter' => '.commission.statementDate',
            ],
            'statementAmount' => [
                'GraphQLschemaToReplace' => [
                    'statementAmount' => null,
                ],
                'jqFilter' => '.commission.statementAmount',
            ],
        ];

        $fieldMapping['WYOCompanyName'] = [
            'GraphQLschemaToReplace' => [
                'agencyData' => [
                    'TbPersonrelation' => [
                        'company' => [
                            'companyName' => null,
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.commission.agencyData.TbPersonrelation[]',
            'parseResultCallback' => 'parseCompanyName',
        ];

        $fieldMapping['AgencyEmail'] = [
            'GraphQLschemaToReplace' => [
                'agencyData' => [
                    'agencyPersonContact' => [
                        'agencyEmailAddress' => null,
                    ],
                ],
            ],
            'jqFilter' => '.commission.agencyData.agencyPersonContact[].agencyEmailAddress',
        ];

        $fieldMapping['CompanyLogo'] = [
            'GraphQLschemaToReplace' => [
                'agencyData' => [
                    'TbPersonrelation' => [
                        'company' => [
                            'logo' => null,
                        ],
                    ]
                ],
            ],
            'jqFilter' => '.commission.agencyData.TbPersonrelation[]',
            'parseResultCallback' => 'parseCompanyLogo',
        ];

        return $fieldMapping;
    }

    public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }

    public function parseCompanyName($brandedCompanyArr)
    {
        $companyName = '';
        if (is_array($brandedCompanyArr) && ! empty($brandedCompanyArr['company']['companyName'])) {
            $companyName = $brandedCompanyArr['company']['companyName'];
        }

        if ($companyName) {
            return $companyName;
        }

        $holdingCompanyDetail = Helper::getHoldingCompanyDetail();
        if (! empty($holdingCompanyDetail['name'])) {
            return $holdingCompanyDetail['name'];
        }

        \Log::info('WORKFLOW - failed to fetch company name ', (array) $brandedCompanyArr);

        return null;
    }

    public function parseCompanyLogo($brandedCompanyArr)
    {
        $logo = '';
        if (is_array($brandedCompanyArr) && ! empty($brandedCompanyArr['company']['logo'])) {
            $logo = $brandedCompanyArr['company']['logo'];
        }

        if (! $logo) {
            $holdingCompanyDetail = Helper::getHoldingCompanyDetail();
            $logo = $holdingCompanyDetail['logo'] ?? null;
        }

        if (! $logo) {
            \Log::info('WORKFLOW - failed to fetch logo ', (array) $brandedCompanyArr);
        }

        // From gfs-saas-infra/src/Foundation/Helpers.php
        $path = removeS3HostAndBucketFromURL($logo);
        \Log::info('WORKFLOW - S3 path for company logo: ' . $path);

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return \Storage::disk('s3')->temporaryUrl($path, Carbon::now()->addMinutes(4320));
    }
}
