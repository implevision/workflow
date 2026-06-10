<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

class Inspection extends AbstractSchema
{
    protected $fieldMapping = [];

    protected $queryName = 'inspection';

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
            'AssignmentId' => [
                'GraphQLschemaToReplace' => ['claim' => ['assignmentId' => null]],
                'jqFilter' => '.inspection.claim.assignmentId',
            ],
            'PolicyNo' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => '.inspection.claim.policy.policyNumber',
            ],
            'DateOfLoss' => [
                'GraphQLschemaToReplace' => ['claim' => ['dateOfLoss' => null]],
                'jqFilter' => '.inspection.claim.dateOfLoss',
            ],
            'AdjusterName' => [
                'GraphQLschemaToReplace' => ['inspector' => ['fullName' => null]],
                'jqFilter' => '.inspection.inspector.fullName',
            ],
            'AdjusterPhone' => [
                'GraphQLschemaToReplace' => ['inspector' => ['phoneInfo' => ['sPhoneNumber' => null]]],
                'jqFilter' => '.inspection.inspector.phoneInfo.sPhoneNumber',
            ],
            'AdjusterEmail' => [
                'GraphQLschemaToReplace' => ['inspector' => ['email' => null]],
                'jqFilter' => '.inspection.inspector.email',
            ],
            'AdjusterFCN' => [
                'GraphQLschemaToReplace' => ['inspector' => ['fcnDocument' => ['sDocumentNumber' => null]]],
                'jqFilter' => '.inspection.inspector.fcnDocument.sDocumentNumber',
            ],
        ];
    }
}
