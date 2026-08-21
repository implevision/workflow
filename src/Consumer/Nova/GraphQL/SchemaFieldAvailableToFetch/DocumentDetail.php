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
        return [
            'DocumentId' => [
                'GraphQLschemaToReplace' => ['id' => null],
                'jqFilter' => '.id',
            ],
            'DocName' => [
                'GraphQLschemaToReplace' => ['docName' => null],
                'jqFilter' => '.docName',
            ],
            'DocGroupType' => [
                'GraphQLschemaToReplace' => ['groupType' => ['name' => null]],
                'jqFilter' => '.groupType.name',
            ],
            'ReportType' => [
                'GraphQLschemaToReplace' => ['groupType' => ['odysseyReportType' => null]],
                'jqFilter' => '.groupType.odysseyReportType',
            ],
            'IsApproved' => [
                'GraphQLschemaToReplace' => ['isApproved' => null],
                'jqFilter' => '.isApproved',
            ],
            'SourceId' => [
                'GraphQLschemaToReplace' => ['sourceId' => null],
                'jqFilter' => '.sourceId',
            ],
            'SourceCode' => [
                'GraphQLschemaToReplace' => ['sourceCode' => null],
                'jqFilter' => '.sourceCode',
            ],
            'AssignmentId' => [
                'GraphQLschemaToReplace' => ['claim' => ['assignmentId' => null]],
                'jqFilter' => '.claim.assignmentId',
            ],
            'PolicyNo' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => '.claim.policy.policyNumber',
            ],
            'DateOfLoss' => [
                'GraphQLschemaToReplace' => ['claim' => ['dateOfLoss' => null]],
                'jqFilter' => '.claim.dateOfLoss',
            ],
            'PolicyNumberWithoutPrefix' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => '.claim.policy.policyNumber',
            ],
        ];
    }
}
