<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

class Renewal extends AbstractSchema
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
        $this->queryName = 'PolicyRenewal';
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the Renewal.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the Renewal.
     *
     * @return string The name of the GraphQL query for Renewal.
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Initializes the field mapping with GraphQL schema for the Renewal class.
     *
     * KEYS are PLACEHOLDER for the GraphQL schema to be replaced.
     *
     * @return array
     */
    private function initializeFieldMapping()
    {
        $renewalListData = [
            [
                'insuredName' => null,
                'policyNo' => null,
                'premiumAmount' => null,
                'termEndDate' => null,
            ],
        ];

        $expiredSchema = [
            'PoliciesExpiredInLast15Days' => [
                [
                    'companyPhoneNumber' => null,
                    'companyName' => null,
                    'companyAddress' => null,
                    'agentUrl' => null,
                    'agentEmail' => null,
                    'logo' => null,
                    'renewalListData' => $renewalListData,
                ],
            ],
        ];

        $expiringSchema = [
            'PoliciesExpiringIn15Days' => [
                [
                    'companyPhoneNumber' => null,
                    'companyName' => null,
                    'companyAddress' => null,
                    'agentUrl' => null,
                    'agentEmail' => null,
                    'logo' => null,
                    'renewalListData' => $renewalListData,
                ],
            ],
        ];

        $fieldMapping = [
            // --- Full array placeholders ---
            'PoliciesExpiredInLast15Days' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days',
            ],
            'PoliciesExpiringIn15Days' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days',
            ],

            // --- Expired: company-level fields ---
            'ExpiredCompanyName' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].companyName',
            ],
            'ExpiredCompanyPhoneNumber' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].companyPhoneNumber',
            ],
            'ExpiredCompanyAddress' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].companyAddress',
            ],
            'ExpiredAgentUrl' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].agentUrl',
            ],
            'ExpiredLogo' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].logo',
            ],
            'ExpiredAgentEmail' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].agentEmail',
            ],

            // --- Expired: renewalListData fields ---
            'ExpiredInsuredName' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].renewalListData[].insuredName',
            ],
            'ExpiredPolicyNo' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].renewalListData[].policyNo',
            ],
            'ExpiredPremiumAmount' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].renewalListData[].premiumAmount',
            ],
            'ExpiredTermEndDate' => [
                'GraphQLschemaToReplace' => $expiredSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiredInLast15Days[].renewalListData[].termEndDate',
            ],

            // --- Expiring: company-level fields ---
            'ExpiringCompanyName' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days[].companyName',
            ],
            'ExpiringCompanyPhoneNumber' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days[].companyPhoneNumber',
            ],
            'ExpiringCompanyAddress' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days[].companyAddress',
            ],
            'ExpiringAgentUrl' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days[].agentUrl',
            ],
            'ExpiringLogo' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days[].logo',
            ],

            // --- Expiring: renewalListData fields ---
            'ExpiringInsuredName' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days[].renewalListData[].insuredName',
            ],
            'ExpiringPolicyNo' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days[].renewalListData[].policyNo',
            ],
            'ExpiringPremiumAmount' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days[].renewalListData[].premiumAmount',
            ],
            'ExpiringTermEndDate' => [
                'GraphQLschemaToReplace' => $expiringSchema,
                'jqFilter' => '.PolicyRenewal.PoliciesExpiringIn15Days[].renewalListData[].termEndDate',
            ],
        ];

        return $fieldMapping;
    }
}
