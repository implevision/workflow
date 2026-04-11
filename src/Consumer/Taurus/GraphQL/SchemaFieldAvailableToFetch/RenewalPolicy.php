<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;
use Carbon\Carbon;
use Taurus\Workflow\Consumer\Taurus\Helper;


class RenewalPolicy extends AbstractSchema
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

    protected int $page = 1;

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
     * Signals that this class handles its own record extraction via
     * getRecordsFromResponse(), bypassing the jqFilter mechanism.
     */
    public function hasCustomRecordExtraction(): bool
    {
        return true;
    }

    /**
     * Groups expired + expiring agents by email into one record each.
     * Each record contains ExpiredRenewalListData and ExpiringRenewalListData
     * as separate keys so the email template can show two distinct sections.
     */
    public function getRecordsFromResponse(array $response): array
    {
        $expired  = $response['PolicyRenewal']['PoliciesExpiredInLast15Days']  ?? [];
        $expiring = $response['PolicyRenewal']['PoliciesExpiringIn15Days']      ?? [];

        $agentMap = [];

        foreach ($expired as $agent) {
            $email = $agent['agentEmail'] ?? '';
            if (! isset($agentMap[$email])) {
                $agentMap[$email] = $this->baseAgentRecord($agent);
            }
            $agentMap[$email]['ExpiredRenewalListData'] = $this->formatRenewalDates($agent['renewalListData'] ?? []);
        }

        foreach ($expiring as $agent) {
            $email = $agent['agentEmail'] ?? '';
            if (! isset($agentMap[$email])) {
                $agentMap[$email] = $this->baseAgentRecord($agent);
            }
            $agentMap[$email]['ExpiringRenewalListData'] = $this->formatRenewalDates($agent['renewalListData'] ?? []);
        }

        return array_values($agentMap);
    }

    private function baseAgentRecord(array $agent): array
    {
        return [
            'AgentEmail'              => $agent['agentEmail']         ?? '',
            'CompanyName'             => $agent['companyName']        ?? '',
            'CompanyAddress'          => $agent['companyAddress']     ?? '',
            'companyPhoneNumber'      => $agent['companyPhoneNumber'] ?? '',
            'logo'                    => $agent['logo']               ?? '',
            'agentUrl'                => $agent['agentUrl']           ?? '',
            'ExpiredRenewalListData'  => [],
            'ExpiringRenewalListData' => [],
        ];
    }

    /**
     * Returns the date and days arguments required by PolicyRenewal query.
     * date = today's date (dynamic at runtime)
     * days = 15 (fixed renewal window)
     */
    public function getQueryArgs(): array
    {
        return [
            'date' => Carbon::today()->format('Y-m-d'),
            'days' => 15,
            'page' => $this->page,
        ];
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
        // GraphQL schema — sirf query banane ke liye use hota hai.
        // Data extraction getRecordsFromResponse() me hoti hai.
        $agentSchema = [
            [
                'companyPhoneNumber' => null,
                'companyName'        => null,
                'companyAddress'     => null,
                'agentUrl'           => null,
                'agentEmail'         => null,
                'logo'               => null,
                'renewalListData'    => [
                    [
                        'insuredName'   => null,
                        'policyNo'      => null,
                        'premiumAmount' => null,
                        'termEndDate'   => null,
                    ],
                ],
            ],
        ];

        $expiredSchema  = ['PoliciesExpiredInLast15Days' => $agentSchema];
        $expiringSchema = ['PoliciesExpiringIn15Days'    => $agentSchema];
        $bothSchema     = $expiredSchema + $expiringSchema;

        // jqFilter nahi — data getRecordsFromResponse() se aata hai
        return [
            'AgentEmail'              => ['GraphQLschemaToReplace' => $bothSchema],
            'CompanyName'             => ['GraphQLschemaToReplace' => $bothSchema],
            'CompanyAddress'          => ['GraphQLschemaToReplace' => $bothSchema],
            'companyPhoneNumber'      => ['GraphQLschemaToReplace' => $bothSchema],
            'logo'                    => ['GraphQLschemaToReplace' => $bothSchema],
            'agentUrl'                => ['GraphQLschemaToReplace' => $bothSchema],
            'renewalListData'         => ['GraphQLschemaToReplace' => $bothSchema],
            'ExpiredAgentEmail'       => ['GraphQLschemaToReplace' => $expiredSchema],
            'ExpiredCompanyName'      => ['GraphQLschemaToReplace' => $expiredSchema],
            'ExpiredCompanyAddress'   => ['GraphQLschemaToReplace' => $expiredSchema],
            'ExpiredCompanyPhoneNumber'=> ['GraphQLschemaToReplace' => $expiredSchema],
            'ExpiredRenewalListData'  => ['GraphQLschemaToReplace' => $expiredSchema],
            'ExpiringAgentEmail'      => ['GraphQLschemaToReplace' => $expiringSchema],
            'ExpiringCompanyName'     => ['GraphQLschemaToReplace' => $expiringSchema],
            'ExpiringCompanyAddress'  => ['GraphQLschemaToReplace' => $expiringSchema],
            'ExpiringCompanyPhoneNumber'=> ['GraphQLschemaToReplace' => $expiringSchema],
            'ExpiringRenewalListData' => ['GraphQLschemaToReplace' => $expiringSchema],
        ];
    }
        public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }

    private function formatRenewalDates(array $list): array
    {
        return array_map(function ($item) {
            if (! empty($item['termEndDate'])) {
                $item['termEndDate'] = Helper::formatDate($item['termEndDate']);
            }

            return $item;
        }, $list);
    }

}
