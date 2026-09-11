<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Nova\Helper;

class Inspection extends AbstractSchema
{
    protected $fieldMapping = [];

    protected $queryName = 'inspection';

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
            'AdjusterName' => [
                'GraphQLschemaToReplace' => ['inspector' => ['fullName' => null]],
                'jqFilter' => "{$this->queryPath}.inspector.fullName",
            ],
            'AdjusterPhone' => [
                'GraphQLschemaToReplace' => ['inspector' => ['phoneInfo' => ['sPhoneNumber' => null]]],
                'jqFilter' => "{$this->queryPath}.inspector.phoneInfo.sPhoneNumber",
            ],
            'AdjusterEmail' => [
                'GraphQLschemaToReplace' => ['inspector' => ['email' => null]],
                'jqFilter' => "{$this->queryPath}.inspector.email",
            ],
            'AdjusterFCN' => [
                'GraphQLschemaToReplace' => ['inspector' => ['fcnDocument' => ['sDocumentNumber' => null]]],
                'jqFilter' => "{$this->queryPath}.inspector.fcnDocument.sDocumentNumber",
            ],
            // Tenant-level branding, not per-record. An empty jqFilter routes to the
            // callback instead of the GraphQL response.
            'CompanyLogo' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'resolveCompanyLogo',
            ],
            // The "Attach" prefix is what marks this as an email attachment:
            // EmailClient::extractAttachments() collects keys matching /^attach/i.
            'AttachAssignmentForm' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'generateClaimAssignmentForm',
            ],
        ];
    }

    /**
     * Ask nova where its branding lives, so this path and the observer agree.
     * Falls back to the env-driven URL when the consumer predates that class.
     */
    public function resolveCompanyLogo(): string
    {
        if (class_exists(\App\Support\CompanyLogo::class)) {
            return \App\Support\CompanyLogo::url();
        }

        return Helper::parseCompanyLogo();
    }

    /**
     * Render the Claim Assignment Form for the record under workflow.
     *
     * `path` must be readable by file_get_contents(), which is how
     * SES::processAttachment() loads it — hence a presigned URL, not an S3 key.
     *
     * @return array<int, array{name: string, path: string}>
     */
    public function generateClaimAssignmentForm(): array
    {
        $recordIdentifier = getRecordIdentifierForRunningWorkflow();

        if (! $recordIdentifier) {
            return [];
        }

        $inspection = \App\Models\Inspection::find($recordIdentifier);

        if (! $inspection) {
            \Log::warning('WORKFLOW - No inspection found for the assignment form', [
                'recordIdentifier' => $recordIdentifier,
            ]);

            return [];
        }

        return app(\App\Services\ClaimAssignmentFormService::class)->buildAttachment($inspection);
    }
}
