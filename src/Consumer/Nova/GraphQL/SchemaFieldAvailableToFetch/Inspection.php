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
        $queryPath = '.'.$this->queryName;

        return [
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
            'AdjusterName' => [
                'GraphQLschemaToReplace' => ['inspector' => ['fullName' => null]],
                'jqFilter' => "{$queryPath}.inspector.fullName",
            ],
            'AdjusterPhone' => [
                'GraphQLschemaToReplace' => ['inspector' => ['phoneInfo' => ['sPhoneNumber' => null]]],
                'jqFilter' => "{$queryPath}.inspector.phoneInfo.sPhoneNumber",
            ],
            'AdjusterEmail' => [
                'GraphQLschemaToReplace' => ['inspector' => ['email' => null]],
                'jqFilter' => "{$queryPath}.inspector.email",
            ],
            'AdjusterFCN' => [
                'GraphQLschemaToReplace' => ['inspector' => ['fcnDocument' => ['sDocumentNumber' => null]]],
                'jqFilter' => "{$queryPath}.inspector.fcnDocument.sDocumentNumber",
            ],
        ];
    }
}
