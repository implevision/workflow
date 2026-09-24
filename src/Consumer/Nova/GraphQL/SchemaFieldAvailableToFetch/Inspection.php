<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

use App\Services\ClientApiKeyService;
use Taurus\Workflow\Consumer\Nova\Helper;

class Inspection extends AbstractSchema
{
    /** How long the presigned URL handed to SES stays valid. Minutes. */
    private const ATTACHMENT_URL_TTL_MINUTES = 60;

    /** document_group_type.doc_type_code the assignment form is filed under. */
    private const ASSIGNMENT_FORM_DOC_TYPE = 'CLAIM_ASSIGNMENT_FORM';

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

    // No getHeaders() override: the inspection query is unguarded.

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
            // Same field as PolicyNo, under the name the webhook body expects.
            'PolicyNumberWithoutPrefix' => [
                'GraphQLschemaToReplace' => ['claim' => ['policy' => ['policyNumber' => null]]],
                'jqFilter' => "{$this->queryPath}.claim.policy.policyNumber",
            ],
            'DateOfLoss' => [
                'GraphQLschemaToReplace' => ['claim' => ['dateOfLoss' => null]],
                'jqFilter' => "{$this->queryPath}.claim.dateOfLoss",
            ],
            // MM/DD/YYYY copy for email templates; DateOfLoss stays ISO for webhooks.
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
            // Tenant-level, not per-record: empty jqFilter routes to the callback.
            'CompanyLogo' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'resolveCompanyLogo',
            ],
            // For templates that sign off as the firm rather than the product.
            'AdjustingFirmName' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'resolveAdjustingFirmName',
            ],
            'AdjustingFirmPhone' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'resolveAdjustingFirmPhone',
            ],
            // nova-back renders and files the form before dispatching the
            // workflow, so this only locates the stored document and signs it.
            'AttachAssignmentForm' => [
                'GraphQLschemaToReplace' => [
                    'documents' => [
                        'id' => null,
                        'docName' => null,
                        'docPath' => null,
                        'groupType' => [
                            'docTypeCode' => null,
                        ],
                    ],
                ],
                // Highest id only, so that if more than one form is ever filed
                // against an inspection the latest one is the one attached.
                'jqFilter' => '['.$this->queryPath.'.documents[]?
                    | select(.groupType?.docTypeCode? == "'.self::ASSIGNMENT_FORM_DOC_TYPE.'")
                    | { id: (.id? | tonumber? // 0), name: .docName?, path: .docPath? }]
                    | sort_by(.id) | reverse | .[0:1] | map({ name, path })',
                'parseResultCallback' => 'generatePresignedUrl',
            ],
            // The remaining entries are for the webhook action's own placeholders.
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
            'XClientKey' => [
                'GraphQLschemaToReplace' => ['claim' => ['clientId' => null]],
                'jqFilter' => "{$this->queryPath}.claim.clientId",
                'parseResultCallback' => 'resolveXClientKey',
            ],
            'ApiKey' => [
                'GraphQLschemaToReplace' => ['claim' => ['clientId' => null]],
                'jqFilter' => "{$this->queryPath}.claim.clientId",
                'parseResultCallback' => 'resolveApiKey',
            ],
            'ApiSecret' => [
                'GraphQLschemaToReplace' => ['claim' => ['clientId' => null]],
                'jqFilter' => "{$this->queryPath}.claim.clientId",
                'parseResultCallback' => 'resolveApiSecret',
            ],
        ];
    }

    /**
     * Signs each document the filter matched. `path` has to be readable by
     * file_get_contents(), which is how SES::processAttachment() loads it, so a
     * presigned URL is returned rather than the bare S3 key.
     *
     * @param  array<int, array{name: ?string, path: ?string}>  $documents
     * @return array<int, array{name: string, path: string}>
     */
    public function generatePresignedUrl(array $documents): array
    {
        $attachments = [];

        foreach ($documents as $doc) {
            $url = Helper::generatePresignedUrl($doc['path'] ?? '', self::ATTACHMENT_URL_TTL_MINUTES);

            if ($url) {
                $attachments[] = ['name' => $doc['name'] ?? '', 'path' => $url];
            }
        }

        return $attachments;
    }

    public function resolveCompanyLogo(): string
    {
        return Helper::parseCompanyLogo();
    }

    public function resolveAdjustingFirmName(): string
    {
        return Helper::adjustingFirmName();
    }

    public function resolveAdjustingFirmPhone(): string
    {
        return Helper::adjustingFirmPhone();
    }

    public function formatDateOfLossUS($isoDate): string
    {
        return Helper::formatDate($isoDate) ?? '';
    }

    /** Fixed for this module/action, not looked up per record. */
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

        if (! $key) {
            return '';
        }

        return app(ClientApiKeyService::class)->resolveXClientKey($key);
    }

    public function resolveApiKey($clientId): string
    {
        return $this->resolveClientApiKey($clientId)?->api_key ?? '';
    }

    public function resolveApiSecret($clientId): string
    {
        return $this->resolveClientApiKey($clientId)?->api_secret ?? '';
    }

    /** Shared by the ApiKey/ApiSecret/XClientKey resolvers above. */
    private function resolveClientApiKey($clientId)
    {
        if (! $clientId) {
            return null;
        }

        return app(ClientApiKeyService::class)->getValidKeyForClient((int) $clientId);
    }
}
