<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Avatar\Infrastructure\Models\Api\v1\TbPolicy;
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
     * Retrieves the field mapping with GraphQL schema for the LexisNexisErrorModel.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the LexisNexisErrorModel.
     *
     * @return string The name of the GraphQL query for LexisNexisErrorModel.
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
     * Turns every LexisNexis failure on the page into its own record, so each
     * failure produces one action payload (e.g. one agent task).
     */
    public function getRecordsFromResponse(array $response): array
    {
        return array_map(
            fn (array $error) => $this->formatError($error),
            array_filter($this->errorsFromResponse($response), 'is_array')
        );
    }

    /**
     * Extracts the flat list of failures from the queryLexisNexisErrors response.
     */
    private function errorsFromResponse(array $response): array
    {
        return $response[$this->queryName]['data'] ?? [];
    }

    /**
     * Returns the arguments required by the queryLexisNexisErrors query, mapped from the
     * workflow's raw dateTimeInfo config (passed via setQueryArgsContext):
     *   frequency        <- executionFrequency
     *   typeOfFrequency  <- executionFrequencyType (DAY|MONTH|YEAR)
     *   incidentEvent    <- executionEventIncident (AFTER|BEFORE|WITH_IN)
     *   executionEvent   <- executionEvent
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

    /**
     * The window reports currentPage/lastPage rather than paginatorInfo,
     * so advance until the last page is reached.
     */
    public function getNextPageArgs(array $response, array $currentArgs): ?array
    {
        $currentPage = (int) ($response[$this->queryName]['currentPage'] ?? 0);
        $lastPage = (int) ($response[$this->queryName]['lastPage'] ?? 0);

        if (empty($this->errorsFromResponse($response)) || $currentPage >= $lastPage) {
            return null;
        }

        return array_merge($currentArgs, ['page' => $currentPage + 1]);
    }

    /**
     * Initializes the field mapping with GraphQL schema for the LexisNexisErrorModel class.
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
            'currentPage' => null,
            'lastPage' => null,
            'total' => null,
        ];

        return [
            'TransactionId' => ['GraphQLschemaToReplace' => $dataSchema],
            'PolicyMasterId' => ['GraphQLschemaToReplace' => $dataSchema],
            'PolicyNumber' => ['GraphQLschemaToReplace' => $dataSchema],
            'ActionCode' => ['GraphQLschemaToReplace' => $dataSchema],
            'ErrorMessage' => ['GraphQLschemaToReplace' => $dataSchema],
            'DocumentDescription' => ['GraphQLschemaToReplace' => $dataSchema],
            'CreatedAt' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgentTaskNote' => ['GraphQLschemaToReplace' => $dataSchema],
            'AgentTaskId' => ['GraphQLschemaToReplace' => []],
            'AgentTaskTitle' => ['GraphQLschemaToReplace' => []],
        ];
    }

    private function formatError(array $error): array
    {
        $policyNo = $error['policyNo'] ?? '';

        return [
            'TransactionId' => $error['transactionId'] ?? '',
            'PolicyMasterId' => $this->getPolicyMasterId($policyNo),
            'PolicyNumber' => $policyNo,
            'ActionCode' => $error['actionCode'] ?? '',
            'ErrorMessage' => $error['errorMessage'] ?? '',
            'DocumentDescription' => $error['documentDescription'] ?? '',
            'CreatedAt' => ! empty($error['createdAt']) ? Helper::formatDateTime($error['createdAt']) : '',
            'AgentTaskNote' => $this->getAgentTaskNote($error),
            'AgentTaskId' => $this->getAgentTaskId(),
            'AgentTaskTitle' => $this->getAgentTaskTitle(),
        ];
    }

    public function getPolicyMasterId(?string $policyNo): string
    {
        if (empty($policyNo)) {
            return '';
        }

        return (string) (TbPolicy::where('Policy_No', $policyNo)->value('n_PolicyNoId_PK') ?? '');
    }

    /**
     * Combines the failure reason and the document it was raised for into the task note.
     */
    public function getAgentTaskNote(array $error): string
    {
        return implode(' - ', array_filter([
            trim($error['errorMessage'] ?? ''),
            trim($error['documentDescription'] ?? ''),
        ]));
    }

    public function getAgentTaskId(): int
    {
        return 13;
    }

    public function getAgentTaskTitle(): string
    {
        return 'Return Mail';
    }
}
