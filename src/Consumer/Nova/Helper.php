<?php

namespace Taurus\Workflow\Consumer\Nova;

class Helper
{
    /**
     * Resolve the {{CompanyLogo}} placeholder to a fetchable image URL.
     *
     * Consumers set WORKFLOW_COMPANY_LOGO_URL with an optional {tenant} token,
     * e.g. "https://services.example.com/backend/api/company-logo/{tenant}", and
     * own the endpoint. A logo embedded in a sent email must keep resolving long
     * after the send, so the endpoint must not expire.
     */
    public static function parseCompanyLogo(): string
    {
        $template = config('workflow.company_logo_url');
        $tenant = (string) getTenant();

        // Without a real tenant the template expands to a dead URL
        // (".../company-logo/NO_TENANT_FOUND" or a bare trailing slash). Return
        // empty so the placeholder renders as nothing, not a broken image.
        if (! $template || $tenant === '' || $tenant === getNoTenantIdentifier()) {
            return '';
        }

        return str_replace('{tenant}', $tenant, $template);
    }
}
