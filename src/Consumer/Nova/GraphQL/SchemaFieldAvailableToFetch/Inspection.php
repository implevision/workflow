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
            // Tenant-level branding, not per-record: no GraphQLschemaToReplace key
            // (so nothing is added to the query) and an empty jqFilter, which routes
            // to resolveCompanyLogo() below instead of the GraphQL response.
            'CompanyLogo' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'resolveCompanyLogo',
            ],
            // Email attachment, same shape as TbPotransaction's AttachDecPage /
            // AttachRenewalNotice: an "Attach"-prefixed key whose callback returns a
            // list of ['name' => ..., 'path' => ...]. EmailClient::extractAttachments()
            // picks the key up by prefix and SES::processAttachment() reads `path` with
            // file_get_contents(), which is why it is a presigned URL.
            //
            // Unlike the Taurus ones there is no document to select out of the GraphQL
            // response -- the form is generated on the fly -- so this takes the empty
            // jqFilter route and the callback builds the PDF itself.
            'AttachAssignmentForm' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'generateClaimAssignmentForm',
            ],
        ];
    }

    public function resolveCompanyLogo(): string
    {
        return Helper::parseCompanyLogo();
    }

    /**
     * Render the Claim Assignment Form for the record this workflow is running
     * for and return it as an attachment.
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
