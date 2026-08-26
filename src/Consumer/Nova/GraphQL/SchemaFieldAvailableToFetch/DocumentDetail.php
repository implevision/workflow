<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

class DocumentDetail extends AbstractSchema
{
    protected $fieldMapping = [];

    protected $queryName = 'documentDetail';

    public function __construct()
    {
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
        $queryPath = '.'.$this->queryName;

        return [
            'DocumentId' => [
                'GraphQLschemaToReplace' => ['id' => null],
                'jqFilter' => "{$queryPath}.id",
            ],
            'DocName' => [
                'GraphQLschemaToReplace' => ['docName' => null],
                'jqFilter' => "{$queryPath}.docName",
            ],
            'DocGroupType' => [
                'GraphQLschemaToReplace' => ['groupType' => ['name' => null]],
                'jqFilter' => "{$queryPath}.groupType.name",
            ],
            'ReportType' => [
                'GraphQLschemaToReplace' => ['groupType' => ['odysseyReportType' => null]],
                'jqFilter' => "{$queryPath}.groupType.odysseyReportType",
            ],
            'IsApproved' => [
                'GraphQLschemaToReplace' => ['isApproved' => null],
                'jqFilter' => "{$queryPath}.isApproved",
            ],
            'SourceId' => [
                'GraphQLschemaToReplace' => ['sourceId' => null],
                'jqFilter' => "{$queryPath}.sourceId",
            ],
            'SourceCode' => [
                'GraphQLschemaToReplace' => ['sourceCode' => null],
                'jqFilter' => "{$queryPath}.sourceCode",
            ],
            'AssignmentId' => [
                'GraphQLschemaToReplace' => ['claim' => ['assignmentId' => null]],
                'jqFilter' => "{$queryPath}.claim.assignmentId",
            ],
            'PolicyNo' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => "{$queryPath}.claim.policy.policyNumber",
            ],
            'DateOfLoss' => [
                'GraphQLschemaToReplace' => ['claim' => ['dateOfLoss' => null]],
                'jqFilter' => "{$queryPath}.claim.dateOfLoss",
            ],
            'PolicyNumberWithoutPrefix' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => "{$queryPath}.claim.policy.policyNumber",
            ],
        ];
    }
}
