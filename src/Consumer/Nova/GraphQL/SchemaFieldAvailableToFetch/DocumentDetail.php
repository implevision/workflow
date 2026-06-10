<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

class DocumentDetail extends AbstractSchema
{
    protected $fieldMapping = [];

    protected $queryName = 'documentDetail';

    public function __construct()
    {
        $this->fieldMapping = $this->initializeFieldMapping();

        $token = config('workflow.graphql_bearer_token');
        if ($token) {
            $this->headers['Authorization'] = 'Bearer ' . $token;
        }
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
                'jqFilter' => '.documentDetail.id',
            ],
            'DocName' => [
                'GraphQLschemaToReplace' => ['docName' => null],
                'jqFilter' => '.documentDetail.docName',
            ],
            'DocGroupType' => [
                'GraphQLschemaToReplace' => ['groupType' => ['name' => null]],
                'jqFilter' => '.documentDetail.groupType.name',
            ],
            'ReportType' => [
                'GraphQLschemaToReplace' => ['groupType' => ['odysseyReportType' => null]],
                'jqFilter' => '.documentDetail.groupType.odysseyReportType',
            ],
            'IsApproved' => [
                'GraphQLschemaToReplace' => ['isApproved' => null],
                'jqFilter' => '.documentDetail.isApproved',
            ],
            'SourceId' => [
                'GraphQLschemaToReplace' => ['sourceId' => null],
                'jqFilter' => '.documentDetail.sourceId',
            ],
            'SourceCode' => [
                'GraphQLschemaToReplace' => ['sourceCode' => null],
                'jqFilter' => '.documentDetail.sourceCode',
            ],
            'AssignmentId' => [
                'GraphQLschemaToReplace' => ['claim' => ['assignmentId' => null]],
                'jqFilter' => '.documentDetail.claim.assignmentId',
            ],
            'PolicyNo' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => '.documentDetail.claim.policy.policyNumber',
            ],
            'DateOfLoss' => [
                'GraphQLschemaToReplace' => ['claim' => ['dateOfLoss' => null]],
                'jqFilter' => '.documentDetail.claim.dateOfLoss',
            ],
            'PolicyNumberWithoutPrefix' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => '.documentDetail.claim.policy.policyNumber',
            ],
        ];
    }
}
