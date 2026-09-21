<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Taurus\Workflow\Consumer\Nova\Helper;

class Inspection extends AbstractSchema
{
    /** How long the presigned URL handed to SES stays valid. Minutes. */
    private const ATTACHMENT_URL_TTL_MINUTES = 60;

    /** Blade view rendered into the Claim Assignment Form PDF. */
    private const ATTACHMENT_VIEW = 'pdf.claim-assignment-form';

    /**
     * Everything the Claim Assignment Form PDF needs, in one place. Fetched
     * alongside the record (not queried separately in nova-back) so the field
     * mapping stays the single source of truth for "where does this data come
     * from", same as every other placeholder here.
     */
    private const ASSIGNMENT_FORM_SCHEMA = [
        'claim' => [
            'assignmentId' => null,
            'dateOfAssignment' => null,
            'dateOfLoss' => null,
            'carrier' => ['name' => null],
            'policy' => [
                'policyNumber' => null,
                'effectiveDate' => null,
                'expirationDate' => null,
                'insured' => [
                    'primaryFullName' => null,
                    'secondFullName' => null,
                    'contacts' => [
                        'contactName' => null,
                        'homePhone' => null,
                        'cellPhone' => null,
                        'businessPhone' => null,
                        'emailAddress' => null,
                    ],
                    'mailingAddress' => [
                        'addressLine1' => null,
                        'addressLine2' => null,
                        'city' => null,
                        'state' => null,
                        'postalCode' => null,
                        'postalCodeSuffix' => null,
                    ],
                    'propertyAddress' => [
                        'addressLine1' => null,
                        'addressLine2' => null,
                        'city' => null,
                        'state' => null,
                        'postalCode' => null,
                        'postalCodeSuffix' => null,
                    ],
                ],
                'coverages' => [
                    'coverageType' => null,
                    'coverageAmount' => null,
                    'deductibleAmount' => null,
                ],
                'mortgagees' => [
                    'bankPosition' => null,
                    'bankName' => null,
                ],
                'priorLosses' => [
                    'lossDate' => null,
                    'amount' => null,
                    'adjuster' => null,
                ],
                'insuranceAgencies' => [
                    'agencyName' => null,
                    'businessPhone' => null,
                ],
                'policyAttributesMap' => [
                    'floodProgramType' => null,
                    'sfipPolicyType' => null,
                    'buildingOccupancyType' => null,
                    'buildingType' => null,
                    'occupancyType' => null,
                    'foundationType' => null,
                    'constructionType' => null,
                    'firstFloorHeightFt' => null,
                    'firstFloorHeightIn' => null,
                    'floorsInBuilding' => null,
                    'floodOpenings' => null,
                    'floodProofed' => null,
                    'floodZone' => null,
                    'lowestMachineryEquipment' => null,
                    'floorNumber' => null,
                    'firmDate' => null,
                    'firmStatus' => null,
                    'dateOfConstruction' => null,
                    'lowestFloorElevation' => null,
                    'baseFloodElevation' => null,
                    'isElevated' => null,
                    'replacementValue' => null,
                    'primaryResidence' => null,
                    'communityId' => null,
                    'panelNumber' => null,
                    'panelSuffix' => null,
                ],
            ],
        ],
        'inspector' => [
            'fullName' => null,
            'email' => null,
            'phoneInfo' => ['sPhoneNumber' => null],
            'fcnDocument' => ['sDocumentNumber' => null],
        ],
    ];

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

    // No getHeaders() override: nova's inspection query is unguarded, so the
    // default (no headers) from AbstractSchema applies. Confirmed with the
    // workflow team.

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
            // the callback below instead of the GraphQL response.
            'CompanyLogo' => [
                'GraphQLschemaToReplace' => [],
                'jqFilter' => '',
                'parseResultCallback' => 'resolveCompanyLogo',
            ],
            // The adjusting firm this tenant operates as -- for email templates
            // that sign off as the firm rather than as the product.
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
            // The "Attach" prefix is what marks this as an email attachment:
            // EmailClient::extractAttachments() collects keys matching /^attach/i.
            // Fetches the whole record (ASSIGNMENT_FORM_SCHEMA) in one shot, so
            // generateClaimAssignmentForm() below never has to query nova-back's
            // database itself -- this field mapping is the single source of truth
            // for what data the form needs and where it comes from.
            'AttachAssignmentForm' => [
                'GraphQLschemaToReplace' => self::ASSIGNMENT_FORM_SCHEMA,
                'jqFilter' => "{$this->queryPath}",
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

    public function resolveAdjustingFirmName(): string
    {
        return Helper::adjustingFirmName();
    }

    public function resolveAdjustingFirmPhone(): string
    {
        return Helper::adjustingFirmPhone();
    }

    /**
     * Render the Claim Assignment Form for the record under workflow and store
     * it on S3. $record is the already-fetched, already-decoded GraphQL
     * response for this inspection (shape: ASSIGNMENT_FORM_SCHEMA).
     *
     * Renders and uploads here rather than delegating to a nova-back service,
     * so the whole attachment lives in one place.
     *
     * Never throws: a failure here must not block the assignment or stop the
     * email going out, so problems are logged and an empty list is returned,
     * which EmailClient::extractAttachments() treats as "no attachment".
     *
     * `path` must be readable by file_get_contents(), which is how
     * SES::processAttachment() loads it -- hence a presigned URL, not an S3 key.
     *
     * @return array<int, array{name: string, path: string}>
     */
    public function generateClaimAssignmentForm(array $record): array
    {
        try {
            $data = $this->buildAssignmentFormData($record);

            $pdf = Pdf::loadView(self::ATTACHMENT_VIEW, $data)
                ->setPaper('letter', 'portrait')
                ->setOptions(['isRemoteEnabled' => true]);

            $assignmentId = $data['assignmentId'] ?: (string) Str::uuid();
            $fileName = 'Claim_Assignment_Form_'.Str::slug($assignmentId, '_').'.pdf';
            $path = tenant('id').'/'.now()->format('Y').'/'.now()->format('m').'/'.now()->format('d')
                .'/OTHER/claim-assignment-forms/'.$fileName;

            $uploaded = Storage::disk('s3')->put($path, $pdf->output(), 'private');

            if (! $uploaded) {
                Log::error('NOVA_ASSIGNMENT_FORM: S3 upload failed', ['path' => $path]);

                return [];
            }

            $url = Storage::disk('s3')->temporaryUrl(
                $path,
                now()->addMinutes(self::ATTACHMENT_URL_TTL_MINUTES)
            );

            if (! $url) {
                Log::warning('NOVA_ASSIGNMENT_FORM: could not presign the stored PDF', ['path' => $path]);

                return [];
            }

            Log::info('NOVA_ASSIGNMENT_FORM: attachment ready', ['path' => $path]);

            return [['name' => $fileName, 'path' => $url]];
        } catch (\Throwable $e) {
            Log::error('NOVA_ASSIGNMENT_FORM: failed to build the form', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Reshapes the GraphQL record into the flat, pre-formatted array the PDF
     * view expects. Mirrors what nova-back's ClaimAssignmentFormService used to
     * assemble via Eloquent -- same fields, same fallbacks, same layout choices.
     */
    private function buildAssignmentFormData(array $record): array
    {
        $claim = $record['claim'] ?? [];
        $policy = $claim['policy'] ?? [];
        $insured = $policy['insured'][0] ?? [];
        $inspector = $record['inspector'] ?? [];
        $agency = $policy['insuranceAgencies'][0] ?? [];
        $priorLoss = $policy['priorLosses'][0] ?? [];
        $attr = $policy['policyAttributesMap'] ?? [];

        // contacts is a list: the first row is the primary contact and the
        // second, when present, is the secondary contact.
        $contacts = $insured['contacts'] ?? [];
        $contact = $contacts[0] ?? [];
        $contact2 = $contacts[1] ?? [];

        // Carrier = insurer named in the header; holding company = the adjusting
        // firm. Not the same organisation, and the form shows both.
        $holdingCompany = Helper::getHoldingCompanyDetail();

        return [
            'carrierName' => $claim['carrier']['name'] ?? '',
            'companyLogo' => $this->resolveCompanyLogo(),

            'adjustingFirm' => $holdingCompany?->s_HoldingCompanyName ?: ($claim['carrier']['name'] ?? 'N/A'),
            'adjustingFirmPhone' => $holdingCompany?->phone_no ?: 'N/A',

            'dateAssigned' => Helper::formatDate($claim['dateOfAssignment'] ?? null) ?: 'N/A',
            'lossDate' => Helper::formatDate($claim['dateOfLoss'] ?? null) ?: 'N/A',
            'policyNumber' => $policy['policyNumber'] ?? '',
            'assignmentId' => $claim['assignmentId'] ?? '',
            'policyPeriod' => $this->formatPeriod($policy['effectiveDate'] ?? null, $policy['expirationDate'] ?? null),
            // Transaction indicator (e.g. "ENDORSE"). No nova equivalent.
            'edn' => 'N/A',

            'insuredName' => $insured['primaryFullName'] ?? '',
            'additionalInsured' => $insured['secondFullName'] ?: 'N/A',
            'propertyAddress' => $this->formatAddress($insured['propertyAddress'] ?? null),
            'mailingAddress' => $this->formatAddress($insured['mailingAddress'] ?? null),

            'contactName' => ($contact['contactName'] ?? '') ?: 'N/A',
            'contactRelationship' => 'N/A',
            'contactHomePhone' => ($contact['homePhone'] ?? '') ?: 'N/A',
            'contactCellPhone' => ($contact['cellPhone'] ?? '') ?: 'N/A',
            'contactOtherPhone' => ($contact['businessPhone'] ?? '') ?: 'N/A',
            'contactEmail' => ($contact['emailAddress'] ?? '') ?: 'N/A',

            'contact2Name' => ($contact2['contactName'] ?? '') ?: 'N/A',
            'contact2Relationship' => 'N/A',
            'contact2HomePhone' => ($contact2['homePhone'] ?? '') ?: 'N/A',
            'contact2CellPhone' => ($contact2['cellPhone'] ?? '') ?: 'N/A',
            'contact2OtherPhone' => ($contact2['businessPhone'] ?? '') ?: 'N/A',
            'contact2Email' => ($contact2['emailAddress'] ?? '') ?: 'N/A',

            // Two independent columns, matching the reference form's own order.
            // 'N/A' entries have no source in nova and are kept as visible gaps.
            'buildingLeft' => [
                'Rate Method' => ($attr['floodProgramType'] ?? '') ?: 'N/A',
                'Policy Form' => ($attr['sfipPolicyType'] ?? '') ?: 'N/A',
                'Number Of Units' => 'N/A',
                'Occupancy' => ($attr['buildingOccupancyType'] ?? '') ?: 'N/A',
                'Building Type' => ($attr['buildingType'] ?? '') ?: 'N/A',
                'Primary/Secondary' => $this->primarySecondary($attr['primaryResidence'] ?? null),
                'Tenant Indicator' => $this->tenantIndicator($attr['occupancyType'] ?? null),
                'Foundation' => ($attr['foundationType'] ?? '') ?: 'N/A',
                'Number of Floors' => $this->floors($attr['floorsInBuilding'] ?? null),
                'Construction Type' => ($attr['constructionType'] ?? '') ?: 'N/A',
                // nova only stores a Yes/No flag, not the count the form wants.
                'Number Of Flood Openings' => 'N/A',
                'Area Of Permanent Flood Openings (sq. in)' => 'N/A',
                'Engineered Openings' => 'N/A',
                'Community Number' => ($attr['communityId'] ?? '') ?: 'N/A',
                'Map Panel' => trim(($attr['panelNumber'] ?? '').' '.($attr['panelSuffix'] ?? '')) ?: 'N/A',
            ],

            'buildingRight' => [
                'Does Building Contain M&E' => ($attr['lowestMachineryEquipment'] ?? '') !== '' ? 'Yes' : 'No',
                'M&E Located Above First Floor' => $this->machineryAboveFirstFloor($attr['lowestMachineryEquipment'] ?? null),
                'Building Contains Washer, Dryer Or Freezer' => 'N/A',
                'Washer, Dryer Or Freezer Above First Floor' => 'N/A',
                'Enclosure Size' => 'N/A',
                'First Floor Height' => $this->height($attr['firstFloorHeightFt'] ?? null, $attr['firstFloorHeightIn'] ?? null),
                'First Floor Height Method' => 'N/A',
                'Post Firm' => ($attr['firmStatus'] ?? '') ?: 'N/A',
                'Flood Zone' => ($attr['floodZone'] ?? '') ?: 'N/A',
                'Date Of Original Construction' => Helper::formatDate($attr['dateOfConstruction'] ?? null) ?: 'N/A',
                'Substantial Improvement Date' => 'N/A',
                'Firm Date' => Helper::formatDate($attr['firmDate'] ?? null) ?: 'N/A',
            ],

            'coverages' => $this->buildLoopRows($policy['coverages'] ?? [], fn (array $c): array => [
                'type' => $c['coverageType'] ?? '',
                'amount' => Helper::formatCurrency($c['coverageAmount'] ?? null) ?: 'N/A',
                'deductible' => Helper::formatCurrency($c['deductibleAmount'] ?? null) ?: 'N/A',
            ]),

            'mortgagees' => array_values(array_filter($this->buildLoopRows(
                $this->sortByBankPosition($policy['mortgagees'] ?? []),
                fn (array $m): string => trim((string) ($m['bankName'] ?? ''))
            ))),

            'adjusterName' => $inspector['fullName'] ?? '',
            'adjusterEmail' => $inspector['email'] ?? '',
            'adjusterPhone' => ($inspector['phoneInfo']['sPhoneNumber'] ?? '') ?: 'N/A',
            'adjusterFcn' => ($inspector['fcnDocument']['sDocumentNumber'] ?? '') ?: 'N/A',

            'agencyName' => ($agency['agencyName'] ?? '') ?: 'N/A',
            'agencyPhone' => ($agency['businessPhone'] ?? '') ?: 'N/A',

            'priorLossDate' => Helper::formatDate($priorLoss['lossDate'] ?? null) ?: 'N/A',
            // prior_losses.amount is a single total, not split by coverage.
            'priorLossAmount' => Helper::formatCurrency($priorLoss['amount'] ?? null) ?: 'N/A',
            'priorLossAdjuster' => ($priorLoss['adjuster'] ?? '') ?: 'N/A',

            // Source unclear -- left blank pending the client.
            'claimsPhone' => 'N/A',
            'comments' => '',
        ];
    }

    /** Floor counts arrive as a number or a code; the form shows a plain number. */
    private function floors($value): string
    {
        return match ((string) $value) {
            '' => 'N/A',
            'FLDONERFLOOR' => '1',
            'FLDTWOFLOORS' => '2',
            'THREEORMOREFLOORS' => '3 or more',
            default => (string) $value,
        };
    }

    /**
     * lowestMachineryEquipment records where the machinery sits. Live values
     * are Basement, Crawlspace, Enclosure, Ground Level, Other floor and
     * Attic -- the first four are at or below the first floor, the rest above.
     */
    private function machineryAboveFirstFloor($value): string
    {
        if (! $value) {
            return 'N/A';
        }

        $atOrBelowFirstFloor = ['Basement', 'Crawlspace', 'Enclosure', 'Ground Level'];

        return in_array((string) $value, $atOrBelowFirstFloor, true) ? 'No' : 'Yes';
    }

    /** primaryResidence is stored Yes/No; the form shows Primary/Secondary. */
    private function primarySecondary($value): string
    {
        return match ((string) $value) {
            'Yes' => 'Primary',
            'No' => 'Secondary',
            default => 'N/A',
        };
    }

    /** occupancyType carries the tenancy; the form wants a Yes/No indicator. */
    private function tenantIndicator($value): string
    {
        if (! $value) {
            return 'N/A';
        }

        return str_contains(strtolower((string) $value), 'tenant') ? 'Yes' : 'No';
    }

    /** Feet and inches are separate attributes; the form shows them as ft.in. */
    private function height($feet, $inches): string
    {
        if ($feet === null && $inches === null) {
            return 'N/A';
        }

        return ((int) $feet).'.'.((int) $inches);
    }

    private function formatPeriod($from, $to): string
    {
        if (! $from && ! $to) {
            return 'N/A';
        }

        return (Helper::formatDate($from) ?: 'N/A').' to '.(Helper::formatDate($to) ?: 'N/A');
    }

    /**
     * Address lines for the form, which shows 'N/A' rather than an empty cell.
     *
     * @return array<int, string>
     */
    private function formatAddress(?array $address): array
    {
        return Helper::formatAddressLines($address) ?: ['N/A'];
    }

    /**
     * bank_position is stored as Primary/Secondary, so a plain string sort puts
     * Primary first; the form numbers them in that order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sortByBankPosition(array $mortgagees): array
    {
        usort($mortgagees, fn ($a, $b) => ($a['bankPosition'] ?? '') <=> ($b['bankPosition'] ?? ''));

        return $mortgagees;
    }

    public function formatDateOfLossUS($isoDate): string
    {
        return Helper::formatDate($isoDate) ?? '';
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
