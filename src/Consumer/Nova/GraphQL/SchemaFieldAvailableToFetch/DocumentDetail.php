<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

class DocumentDetail extends AbstractSchema
{
    protected $fieldMapping = [];

    protected $queryName = 'documentDetail';

    protected $queryPath;

    public function __construct()
    {
        $this->queryPath = '.'.$this->queryName;
        $this->fieldMapping = $this->initializeFieldMapping();
    }

    public function getFieldMapping(): array
    {
        return $this->fieldMapping;
    }

    public function getQueryName(): string
    {
        return $this->queryName;
    }

    private function initializeFieldMapping(): array
    {
        return [
            'DocumentId' => [
                'GraphQLschemaToReplace' => ['id' => null],
                'jqFilter' => "{$this->queryPath}.id",
            ],
            'DocName' => [
                'GraphQLschemaToReplace' => ['docName' => null],
                'jqFilter' => "{$this->queryPath}.docName",
            ],
            'DocGroupType' => [
                'GraphQLschemaToReplace' => ['groupType' => ['name' => null]],
                'jqFilter' => "{$this->queryPath}.groupType.name",
            ],
            'ReportType' => [
                'GraphQLschemaToReplace' => ['groupType' => ['odysseyReportType' => null]],
                'jqFilter' => "{$this->queryPath}.groupType.odysseyReportType",
            ],
            'IsApproved' => [
                'GraphQLschemaToReplace' => ['isApproved' => null],
                'jqFilter' => "{$this->queryPath}.isApproved",
            ],
            'SourceId' => [
                'GraphQLschemaToReplace' => ['sourceId' => null],
                'jqFilter' => "{$this->queryPath}.sourceId",
            ],
            'SourceCode' => [
                'GraphQLschemaToReplace' => ['sourceCode' => null],
                'jqFilter' => "{$this->queryPath}.sourceCode",
            ],
            'AssignmentId' => [
                'GraphQLschemaToReplace' => ['claim' => ['assignmentId' => null]],
                'jqFilter' => "{$this->queryPath}.claim.assignmentId",
            ],
            'PolicyNo' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => "{$this->queryPath}.claim.policy.policyNumber",
            ],
            'DateOfLoss' => [
                'GraphQLschemaToReplace' => ['claim' => ['dateOfLoss' => null]],
                'jqFilter' => "{$this->queryPath}.claim.dateOfLoss",
            ],
            'PolicyNumberWithoutPrefix' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => "{$this->queryPath}.claim.policy.policyNumber",
            ],
        ];
    }
}
