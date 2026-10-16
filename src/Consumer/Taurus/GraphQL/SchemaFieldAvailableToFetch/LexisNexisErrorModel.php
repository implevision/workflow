<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Avatar\Infrastructure\Models\Api\v1\TbAgentTasksList;
use Avatar\Infrastructure\Models\Api\v1\TbPolicy;
use Taurus\Workflow\Consumer\Taurus\Helper;

class LexisNexisErrorModel extends AbstractSchema
{
    private const RETURN_MAIL_TASK_CODE = 'RETURNMAIL';

    /**
     * @var array|null Cached RETURNMAIL row (id, task_title) from tb_agent_tasks_list.
     */
    private ?array $returnMailTask = null;

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

    /**
     * @var string|null The path of the query associated with this class.
     */
    protected $queryPath;

    public function __construct()
    {
        $this->queryName = 'lexisNexisErrorsQuery';
        $this->queryPath = '.'.$this->queryName;
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the LexisNexisErrorModel.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        if (empty($this->fieldMapping)) {
            $this->fieldMapping = $this->initializeFieldMapping();
        }

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
     * The error's Id is always fetched for CREATE_RECORD actions so the agent task
     * handler can store the created mapping on the tb_lexisnexis_errors row.
     */
    public function getRequiredPlaceholdersForCreateRecord(): array
    {
        return ['Id'];
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
        $fieldMapping = [
            'Id' => [
                'GraphQLschemaToReplace' => [
                    'id' => null,
                ],
                'jqFilter' => "{$this->queryPath}.id",
            ],
            'TransactionId' => [
                'GraphQLschemaToReplace' => [
                    'transactionId' => null,
                ],
                'jqFilter' => "{$this->queryPath}.transactionId",
            ],
            'PolicyMasterId' => [
                'GraphQLschemaToReplace' => [
                    'policyNo' => null,
                ],
                'jqFilter' => "{$this->queryPath}.policyNo",
                'parseResultCallback' => 'getPolicyMasterId',
            ],
            'PolicyNumber' => [
                'GraphQLschemaToReplace' => [
                    'policyNo' => null,
                ],
                'jqFilter' => "{$this->queryPath}.policyNo",
            ],
            'ActionCode' => [
                'GraphQLschemaToReplace' => [
                    'actionCode' => null,
                ],
                'jqFilter' => "{$this->queryPath}.actionCode",
            ],
            'ErrorMessage' => [
                'GraphQLschemaToReplace' => [
                    'errorMessage' => null,
                ],
                'jqFilter' => "{$this->queryPath}.errorMessage",
            ],
            'DocumentDescription' => [
                'GraphQLschemaToReplace' => [
                    'documentDescription' => null,
                ],
                'jqFilter' => "{$this->queryPath}.documentDescription",
            ],
            'CreatedAt' => [
                'GraphQLschemaToReplace' => [
                    'createdAt' => null,
                ],
                'jqFilter' => "{$this->queryPath}.createdAt",
                'parseResultCallback' => 'formatCreatedAt',
            ],
            'AgentTaskNote' => [
                'GraphQLschemaToReplace' => [
                    'errorMessage' => null,
                    'documentDescription' => null,
                ],
                'jqFilter' => "{$this->queryPath} | {errorMessage, documentDescription}",
                'parseResultCallback' => 'getAgentTaskNote',
            ],
            'AgentTaskId' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => null,
                'parseResultCallback' => 'getAgentTaskId',
            ],
            'AgentTaskTitle' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => null,
                'parseResultCallback' => 'getAgentTaskTitle',
            ],
        ];

        return $this->wrapFieldMappingSchemaUnderData($fieldMapping);
    }

    public function getPolicyMasterId($policyNo): string
    {
        if (empty($policyNo) || $policyNo === 'null') {
            return '';
        }

        return (string) (TbPolicy::where('Policy_No', (string) $policyNo)->value('n_PolicyNoId_PK') ?? '');
    }

    public function formatCreatedAt($createdAt): string
    {
        return ! empty($createdAt) && $createdAt !== 'null' ? Helper::formatDateTime($createdAt) : '';
    }

    /**
     * Combines the failure reason and the document it was raised for into the task note.
     */
    public function getAgentTaskNote($error): string
    {
        if (! is_array($error)) {
            return '';
        }

        return implode(' - ', array_filter([
            trim((string) ($error['errorMessage'] ?? '')),
            trim((string) ($error['documentDescription'] ?? '')),
        ]));
    }

    public function getAgentTaskId(): int
    {
        return (int) ($this->getReturnMailTask()['id'] ?? 0);
    }

    public function getAgentTaskTitle(): string
    {
        return (string) ($this->getReturnMailTask()['task_title'] ?? '');
    }

    /**
     * The RETURNMAIL task from tb_agent_tasks_list, looked up once per run
     * since the callbacks are invoked for every record.
     */
    private function getReturnMailTask(): array
    {
        if ($this->returnMailTask === null) {
            $task = TbAgentTasksList::where('task_code', self::RETURN_MAIL_TASK_CODE)
                ->where('is_active', 'Y')
                ->where('is_deleted', 'N')
                ->first(['id', 'task_title']);

            $this->returnMailTask = $task ? $task->toArray() : [];
        }

        return $this->returnMailTask;
    }
}
