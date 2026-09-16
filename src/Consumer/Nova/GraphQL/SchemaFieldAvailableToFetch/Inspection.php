<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

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

    // No getHeaders() override: nova's inspection query is unguarded, same as
    // Taurus's endpoint, so the default (no headers) from AbstractSchema applies.
    // Confirmed with the workflow team.

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
            // Same underlying field as PolicyNo. The webhook body uses this exact
            // name (matching what InspectionWorkflowObserver used to push), so it
            // is kept as its own placeholder rather than asking the webhook config
            // to be renamed.
            'PolicyNumberWithoutPrefix' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => "{$this->queryPath}.claim.policy.policyNumber",
            ],
            'DateOfLoss' => [
                'GraphQLschemaToReplace' => ['claim' => ['dateOfLoss' => null]],
                'jqFilter' => "{$this->queryPath}.claim.dateOfLoss",
            ],
            // DateOfLoss stays ISO (webhook consumers may validate that format);
            // this is the MM/DD/YYYY copy for email templates. Same GraphQL field,
            // reformatted by parseResultCallback after extraction.
            'DateOfLossUS' => [
                'GraphQLschemaToReplace' => ['claim' => ['dateOfLoss' => null]],
                'jqFilter' => "{$this->queryPath}.claim.dateOfLoss",
                'parseResultCallback' => 'formatDateOfLossUS',
            ],
            'InsuredName' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['insured' => ['primaryFullName' => null]]]],
                'jqFilter' => "{$this->queryPath}.claim.policy.insured[0].primaryFullName",
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
            // (nothing added to the query) and an empty jqFilter, which routes to
            // resolveCompanyLogo() below instead of the GraphQL response.
            'CompanyLogo' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'resolveCompanyLogo',
            ],
            // The "Attach" prefix is what marks this as an email attachment:
            // EmailClient::extractAttachments() collects keys matching /^attach/i.
            // No document to select out of the GraphQL response -- the form is
            // generated on the fly -- so this also takes the empty-jqFilter route.
            'AttachAssignmentForm' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'generateClaimAssignmentForm',
            ],
            // The remaining entries exist only so the webhook action (the other
            // action on this workflow) can resolve its own placeholders --
            // {{Type}}, {{SubType}}, {{X-Client-key}}, {{api_key}}, {{api_secret}}
            // -- the same way the email action resolves {{AdjusterName}} and the
            // rest. This is what used to be pushed directly by
            // InspectionWorkflowObserver; moving it here is what let the observer
            // stop pushing an entity payload at all.
            'Type' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'resolveType',
            ],
            'SubType' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'resolveSubType',
            ],
            'X-Client-key' => [
                'GraphQLschemaToReplace' => ['claim' => ['clientId' => null]],
                'jqFilter' => "{$this->queryPath}.claim.clientId",
                'parseResultCallback' => 'resolveXClientKey',
            ],
            'api_key' => [
                'GraphQLschemaToReplace' => ['claim' => ['clientId' => null]],
                'jqFilter' => "{$this->queryPath}.claim.clientId",
                'parseResultCallback' => 'resolveApiKey',
            ],
            'api_secret' => [
                'GraphQLschemaToReplace' => ['claim' => ['clientId' => null]],
                'jqFilter' => "{$this->queryPath}.claim.clientId",
                'parseResultCallback' => 'resolveApiSecret',
            ],
        ];
    }

    public function resolveCompanyLogo(): string
    {
        if (class_exists(\App\Support\CompanyLogo::class)) {
            return \App\Support\CompanyLogo::url();
        }

        return '';
    }

    /**
     * Render the Claim Assignment Form for the record under workflow.
     *
     * `path` must be readable by file_get_contents(), which is how
     * SES::processAttachment() loads it -- hence a presigned URL, not an S3 key.
     *
     * @return array<int, array{name: string, path: string}>
     */
    public function generateClaimAssignmentForm(): array
    {
        $recordIdentifier = getRecordIdentifierForRunningWorkflow();

        if (! $recordIdentifier) {
            return [];
        }

        if (! class_exists(\App\Services\ClaimAssignmentFormService::class)) {
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

    public function formatDateOfLossUS($isoDate): string
    {
        if (! $isoDate) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($isoDate)->format('m/d/Y');
        } catch (\Throwable) {
            return (string) $isoDate;
        }
    }

    /**
     * Constant for this module/action -- not looked up per record. Matches what
     * InspectionWorkflowObserver used to hardcode directly into the payload.
     */
    public function resolveType(): string
    {
        return 'INSPECTION';
    }

    public function resolveSubType(): string
    {
        return 'ADJUSTER_ASSIGNMENT';
    }

    public function resolveXClientKey($clientId): string
    {
        $key = $this->resolveClientApiKey($clientId);

        if (! $key || ! class_exists(\App\Services\ClientApiKeyService::class)) {
            return '';
        }

        return app(\App\Services\ClientApiKeyService::class)->resolveXClientKey($key);
    }

    public function resolveApiKey($clientId): string
    {
        return $this->resolveClientApiKey($clientId)?->api_key ?? '';
    }

    public function resolveApiSecret($clientId): string
    {
        return $this->resolveClientApiKey($clientId)?->api_secret ?? '';
    }

    /**
     * Shared lookup behind the three api_key/api_secret/X-Client-key resolvers
     * above -- one client-id-to-key lookup per placeholder is acceptable here:
     * this runs once per workflow dispatch, not per record in a batch.
     */
    private function resolveClientApiKey($clientId)
    {
        if (! $clientId || ! class_exists(\App\Services\ClientApiKeyService::class)) {
            return null;
        }

        return app(\App\Services\ClientApiKeyService::class)->getValidKeyForClient((int) $clientId);
    }
}
