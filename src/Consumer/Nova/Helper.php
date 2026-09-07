<?php

namespace Taurus\Workflow\Consumer\Nova;

class Helper
{
    /**
     * Resolve the {{CompanyLogo}} placeholder to a fetchable image URL.
     *
     * Consumers set WORKFLOW_COMPANY_LOGO_URL with an optional {tenant} token,
     * e.g. "https://services.example.com/backend/api/company-logo/{tenant}", and
     * own the endpoint itself, since only the consumer knows where its branding
     * lives.
     *
     * A URL template rather than a stored value on purpose: a logo embedded in a
     * sent email has to keep resolving long after the send, so presigned S3 URLs
     * are unusable here. In nova, logo_url holds an S3 key and public_logo_url a
     * 60-minute presigned URL, so neither column can be handed to a mail client.
     *
     * Lives on the Helper rather than a schema class because consumers supply
     * placeholder values from two places -- a model observer building the entity
     * payload, and a GraphQL field mapping -- and both need the same answer.
     * DispatchWorkflowService derives isManuallyInvoked from count($data), so a
     * non-empty entity payload is used verbatim and the field mapping is never
     * consulted.
     */
    public static function parseCompanyLogo(): string
    {
        $template = config('workflow.company_logo_url');
        $tenant = (string) getTenant();

        // Without a real tenant the template would expand to a URL that resolves to
        // nothing -- ".../company-logo/NO_TENANT_FOUND" when the consumer is not
        // tenanted, or ".../company-logo/" when tenant('id') is null because no
        // tenant was initialised. Return empty so the placeholder renders as
        // nothing rather than as a broken image.
        if (! $template || $tenant === '' || $tenant === getNoTenantIdentifier()) {
            return '';
        }

        return str_replace('{tenant}', $tenant, $template);
    }
}
