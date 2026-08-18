<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

class CeReview extends AbstractSchema
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
        $this->queryName = 'queryCeReviews';
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the CE review digest.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the CE review digest.
     *
     * @return string The name of the GraphQL query.
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
     * Groups the reviewed CE licenses by agent email so one agent gets exactly
     * one record - and therefore one consolidated email - no matter how many CE
     * licenses were reviewed for them inside the window.
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

            $agentMap[$email]['CeReviewListData'] = array_merge(
                $agentMap[$email]['CeReviewListData'],
                $this->formatCeReviews($agent['ceReviewList'] ?? [])
            );
        }

        return array_values(array_filter($agentMap, function ($agent) {
            return ! empty($agent['CeReviewListData']);
        }));
    }

    /**
     * Extracts the flat list of agent groups from the CE review response.
     */
    private function agentsFromResponse(array $response): array
    {
        return $response['queryCeReviews']['data'] ?? [];
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
            'CurrentYear' => $this->getCurrentYear(),
            'CeReviewListData' => [],
        ];
    }

    /**
     * Returns the arguments required by the CE review query, mapped from the
     * workflow's raw dateTimeInfo config (passed via setQueryArgsContext):
     *   frequency        <- executionFrequency
     *   typeOfFrequency  <- executionFrequencyType (HOUR|DAY|WEEK|MONTH|YEAR)
     *   incidentEvent    <- executionEventIncident (AFTER|BEFORE|WITH_IN)
     *   executionEvent   <- executionEvent (CE_REVIEWS - the review completed date)
     *
     * The frequency gives one cut off date, incidentEvent picks the side:
     * WITH_IN stays between that date and now, AFTER takes everything from that
     * date onwards, BEFORE everything older. That window is what turns
     * "one email per reviewed CE" into "one email per agent per period" -
     * everything inside it lands in one record.
     */
    public function getQueryArgs(): array
    {
        $context = $this->queryArgsContext;

        $args = [
            'frequency' => (int) (($context['executionFrequency'] ?? null) ?: 1),
            'typeOfFrequency' => $context['executionFrequencyType'] ?? 'DAY',
            'incidentEvent' => $context['executionEventIncident'] ?? 'WITH_IN',
            'executionEvent' => $context['executionEvent'] ?? null,
            'page' => $this->page,
        ];

        // Drop null/empty args so the generated GraphQL stays valid (no "executionEvent: " gaps).
        return array_filter($args, fn ($value) => $value !== null && $value !== '');
    }

    public function getNextPageArgs(array $response, array $currentArgs): ?array
    {
        $agents = $this->agentsFromResponse($response);

        $hasCeReviewData = false;
        foreach ($agents as $agent) {
            if (! empty($agent['ceReviewList'])) {
                $hasCeReviewData = true;
                break;
            }
        }

        if (! $hasCeReviewData) {
            return null;
        }

        return array_merge($currentArgs, ['page' => $currentArgs['page'] + 1]);
    }

    /**
     * Initializes the field mapping with GraphQL schema for the CE review digest.
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
                'ceReviewList' => [
                    [
                        'producerName' => null,
                        'username' => null,
                        'licenseNo' => null,
                        'trainingProvider' => null,
                        'trainingProviderNo' => null,
                        'courseName' => null,
                        'courseNumber' => null,
                        'courseHours' => null,
                        'courseCompletionDate' => null,
                        'courseExpirationDate' => null,
                        'status' => null,
                        'ceRejectionReason' => null,
                        'reviewedOn' => null,
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
            'CeReviewListData' => ['GraphQLschemaToReplace' => $dataSchema],
            'CurrentYear' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'getCurrentYear',
            ],
        ];
    }

    public function formatDate($dateToFormat)
    {
        return Helper::formatDate($dateToFormat);
    }

    public function getCurrentYear()
    {
        return date('Y');
    }

    /**
     * One row per reviewed CE inside the window - this is the {{#each}} list the
     * consolidated email renders. CeRejectionReason only carries a value on a
     * rejected CE, an approved one leaves it empty.
     */
    private function formatCeReviews(array $list): array
    {
        return array_map(function ($item) {
            return [
                'ProducerName' => $item['producerName'] ?? '',
                'Username' => $item['username'] ?? '',
                'LicenseNo' => $item['licenseNo'] ?? '',
                'TrainingProvider' => $item['trainingProvider'] ?? '',
                'TrainingProviderNo' => $item['trainingProviderNo'] ?? '',
                'CourseName' => $item['courseName'] ?? '',
                'CourseNumber' => $item['courseNumber'] ?? '',
                'CourseHours' => $item['courseHours'] ?? '',
                'CourseCompletionDate' => ! empty($item['courseCompletionDate']) ? Helper::formatDate($item['courseCompletionDate']) : '',
                'CourseExpirationDate' => ! empty($item['courseExpirationDate']) ? Helper::formatDate($item['courseExpirationDate']) : '',
                'CeStatus' => $item['status'] ?? '',
                'CeRejectionReason' => $item['ceRejectionReason'] ?? '',
                'ReviewedOn' => ! empty($item['reviewedOn']) ? Helper::formatDate($item['reviewedOn']) : '',
            ];
        }, $list);
    }
}
