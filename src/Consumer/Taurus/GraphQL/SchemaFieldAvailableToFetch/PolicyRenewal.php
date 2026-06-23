<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

class PolicyRenewal extends AbstractSchema
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
        $this->queryName = 'queryPolicyRenewal';
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
     * Groups agents by email into one record each. The query now returns a
     * single `data` set whose direction is decided by incidentEvent/executionEvent,
     * so there is no longer an expired/expiring split here.
     */
    public function getRecordsFromResponse(array $response): array
    {
        $agents = $this->agentsFromResponse($response);

        $agentMap = [];

        foreach ($agents as $agent) {
            $email = $agent['agentEmail'] ?? '';
            if (! isset($agentMap[$email])) {
                $agentMap[$email] = $this->baseAgentRecord($agent);
            }
            $agentMap[$email]['RenewalListData'] = $this->formatRenewalDates($agent['renewalListData'] ?? []);
        }

        return array_values(array_filter($agentMap, function ($agent) {
            return ! empty($agent['RenewalListData']);
        }));
    }

    /**
     * Extracts the flat list of agent groups from the policyRenewal response.
     */
    private function agentsFromResponse(array $response): array
    {
        return $response['queryPolicyRenewal']['data'] ?? [];
    }

    private function baseAgentRecord(array $agent): array
    {
        return [
            'Logo' => $agent['logo'] ?? '',
            'AgentUrl' => $agent['agentUrl'] ?? '',
            'CompanyName' => $agent['companyName'] ?? '',
            'CompanyAddress' => $agent['companyAddress'] ?? '',
            'CompanyPhoneNumber' => $agent['companyPhoneNumber'] ?? '',
            'AgentEmail' => $agent['agentEmail'] ?? '',
            'AgencyEmail' => $agent['agencyEmail'] ?? '',
            'AgentFloodCode' => $agent['agentFloodCode'] ?? '',
            'AgentFullName' => $agent['agentFullName'] ?? '',
            'AgencyFloodCode' => $agent['agencyFloodCode'] ?? '',
            'AgencyFullName' => $agent['agencyFullName'] ?? '',
            'AgencyAccountId' => $agent['agencyAccountId'] ?? '',
            'RenewalListData' => [],
        ];
    }

    /**
     * Returns the arguments required by the policyRenewal query, mapped from the
     * workflow's raw dateTimeInfo config (passed via setQueryArgsContext):
     *   frequency        <- executionFrequency
     *   typeOfFrequency  <- executionFrequencyType (DAY|MONTH|YEAR)
     *   incidentEvent    <- executionEventIncident (AFTER|BEFORE)
     *   executionEvent   <- executionEvent
     */
    public function getQueryArgs(): array
    {
        $context = $this->queryArgsContext;

        $args = [
            'frequency' => (int) (($context['executionFrequency'] ?? null) ?: 15),
            'typeOfFrequency' => $context['executionFrequencyType'] ?? 'DAY',
            'incidentEvent' => $context['executionEventIncident'] ?? 'AFTER',
            'executionEvent' => $context['executionEvent'] ?? null,
            'page' => $this->page,
        ];

        // Drop null/empty args so the generated GraphQL stays valid (no "executionEvent: " gaps).
        return array_filter($args, fn ($value) => $value !== null && $value !== '');
    }

    public function getNextPageArgs(array $response, array $currentArgs): ?array
    {
        $agents = $this->agentsFromResponse($response);

        $hasRenewalData = false;
        foreach ($agents as $agent) {
            if (! empty($agent['renewalListData'])) {
                $hasRenewalData = true;
                break;
            }
        }

        if (! $hasRenewalData) {
            return null;
        }

        return array_merge($currentArgs, ['page' => $currentArgs['page'] + 1]);
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
        $agentSchema = [
            [
                'logo' => null,
                'agentUrl' => null,
                'companyName' => null,
                'companyAddress' => null,
                'companyPhoneNumber' => null,
                'agentEmail' => null,
                'agencyEmail' => null,
                'agentFloodCode' => null,
                'agentFullName' => null,
                'agencyFloodCode' => null,
                'agencyFullName' => null,
                'agencyAccountId' => null,
                'renewalListData' => [
                    [
                        'policyNo' => null,
                        'insuredName' => null,
                        'termEndDate' => null,
                        'premiumAmount' => null,
                        'paymentAmt' => null,
                    ],
                ],
            ],
        ];

        $dataSchema = ['data' => $agentSchema];

        return [
            'Logo' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgentUrl' => ['GraphQLschemaToReplace' => $dataSchema],
            'CompanyName' => ['GraphQLschemaToReplace' => $dataSchema],
            'CompanyAddress' => ['GraphQLschemaToReplace' => $dataSchema],
            'CompanyPhoneNumber' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgentEmail' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgencyEmail' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgentFloodCode' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgentFullName' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgencyFloodCode' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgencyFullName' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgencyAccountId' => ['GraphQLschemaToReplace' => $dataSchema],
            'RenewalListData' => ['GraphQLschemaToReplace' => $dataSchema],
        ];
    }

    public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }

    private function formatRenewalDates(array $list): array
    {
        return array_map(function ($item) {
            return [
                'PolicyNo'      => $item['policyNo'] ?? '',
                'InsuredName'   => $item['insuredName'] ?? '',
                'TermEndDate'   => ! empty($item['termEndDate']) ? Helper::formatDate($item['termEndDate']) : '',
                'PremiumAmount' => $item['premiumAmount'] ?? '',
                'PaymentAmt'    => $item['paymentAmt'] ?? '',
            ];
        }, $list);
    }
}
