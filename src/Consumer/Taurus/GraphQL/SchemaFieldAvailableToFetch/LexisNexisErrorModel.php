<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

class LexisNexisErrorModel extends AbstractSchema
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
        $this->queryName = 'queryLexisNexisErrors';
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the LexisNexis failure digest.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the LexisNexis failure digest.
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
     * The query returns a flat list of failures, so the whole page becomes one
     * record - and therefore one email - listing every failure on it.
     */
    public function getRecordsFromResponse(array $response): array
    {
        $errors = $this->errorsFromResponse($response);

        if (empty($errors)) {
            return [];
        }

        return [[
            'CurrentYear' => $this->getCurrentYear(),
            'LexisNexisErrorListData' => $this->formatErrors($errors),
        ]];
    }

    /**
     * Extracts the flat list of failures from the LexisNexis response.
     */
    private function errorsFromResponse(array $response): array
    {
        return $response['queryLexisNexisErrors']['data'] ?? [];
    }

    /**
     * Returns the arguments required by the LexisNexis query, mapped from the
     * workflow's raw dateTimeInfo config (passed via setQueryArgsContext):
     *   frequency        <- executionFrequency
     *   typeOfFrequency  <- executionFrequencyType (DAY|MONTH|YEAR)
     *   incidentEvent    <- executionEventIncident (AFTER|BEFORE|WITH_IN)
     *   executionEvent   <- executionEvent (date column the window applies to)
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
        if (empty($this->errorsFromResponse($response))) {
            return null;
        }

        return array_merge($currentArgs, ['page' => $currentArgs['page'] + 1]);
    }

    /**
     * Initializes the field mapping with GraphQL schema for the LexisNexis failure digest.
     *
     * KEYS are PLACEHOLDER for the GraphQL schema to be replaced.
     *
     * @return array
     */
    private function initializeFieldMapping()
    {
        $dataSchema = [
            'data' => [
                [
                    'transactionId' => null,
                    'policyNo' => null,
                    'actionCode' => null,
                    'errorMessage' => null,
                    'documentDescription' => null,
                    'createdAt' => null,
                ],
            ],
        ];

        return [
            'LexisNexisErrorListData' => ['GraphQLschemaToReplace' => $dataSchema],
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
     * One row per failure inside the window - this is the {{#each}} list the
     * consolidated email renders.
     */
    private function formatErrors(array $list): array
    {
        return array_map(function ($item) {
            return [
                'TransactionId' => $item['transactionId'] ?? '',
                'PolicyNo' => $item['policyNo'] ?? '',
                'ActionCode' => $item['actionCode'] ?? '',
                'ErrorMessage' => $item['errorMessage'] ?? '',
                'DocumentDescription' => $item['documentDescription'] ?? '',
                'CreatedAt' => ! empty($item['createdAt']) ? Helper::formatDateTime($item['createdAt']) : '',
            ];
        }, $list);
    }
}
