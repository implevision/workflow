<?php

namespace Taurus\Workflow\Consumer\Nova\GraphQL\SchemaFieldAvailableToFetch;

class AbstractSchema
{
    protected $headers = [];

    protected int $page = 0;

    protected $appendedPlaceHolders = [];

    protected array $queryArgsContext = [];

    public function setAppendedPlaceHolders(array $appendedPlaceHolders)
    {
        $this->appendedPlaceHolders = $appendedPlaceHolders;
    }

    public function getAppendedPlaceHolders(): array
    {
        return $this->appendedPlaceHolders;
    }

    public function setQueryArgsContext(array $queryArgsContext): void
    {
        $this->queryArgsContext = $queryArgsContext;
    }

    public function getQueryArgsContext(): array
    {
        return $this->queryArgsContext;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getQueryArgs(): array
    {
        return [];
    }

    /**
     * Nova modules query a single record directly under the query root
     * (no `{data: [...], paginatorInfo: {...}}` wrapper), so pagination
     * doesn't apply by default. Override to return true for a module whose
     * query does return a page of records.
     */
    public function supportsPagination(): bool
    {
        return false;
    }

    /**
     * Nova modules don't support pagination by default (see
     * supportsPagination()), so their queries never request paginatorInfo —
     * there's nothing to check it against. Override alongside
     * supportsPagination() for a module that does paginate.
     */
    public function getNextPageArgs(array $response, array $currentArgs): ?array
    {
        return null;
    }

    public function hasCustomRecordExtraction(): bool
    {
        return false;
    }

    public function getRecordsFromResponse(array $response): array
    {
        return [];
    }

    protected function buildLoopRows(array|string $items, callable $rowMapper): array
    {
        if (\is_string($items)) {
            $items = json_decode($items, true);
        }

        if (! \is_array($items)) {
            return [];
        }

        return array_values(array_map($rowMapper, array_filter($items, 'is_array')));
    }

    protected function wrapFieldMappingSchemaUnderData(array $fieldMapping): array
    {
        foreach ($fieldMapping as &$mapping) {
            if (! empty($mapping['GraphQLschemaToReplace'])) {
                $mapping['GraphQLschemaToReplace'] = ['data' => $mapping['GraphQLschemaToReplace']];
            }
        }
        unset($mapping);

        return $fieldMapping;
    }

    public function fetchAllData($client, $builder, array $schemaData, array $graphQLQuery): array
    {
        return [];
    }

    /**
     * Resolve the {{CompanyLogo}} placeholder to a fetchable image URL.
     *
     * Declared here rather than per-consumer because every consumer needs the
     * same thing: a URL a mail client can fetch, forever. Consumers configure
     * WORKFLOW_COMPANY_LOGO_URL with an optional {tenant} token, e.g.
     * "https://api.example.com/company-logo/{tenant}"; the consumer owns the
     * endpoint, since only it knows where its branding lives.
     *
     * Do not point this at a presigned S3 URL — those expire, and a logo baked
     * into a sent email must keep resolving long after the send.
     *
     * Wire it up in a field mapping with no jqFilter, which routes the
     * placeholder to this callback instead of the GraphQL response:
     *
     *   'CompanyLogo' => ['jqFilter' => '', 'parseResultCallback' => 'resolveCompanyLogo'],
     */
    public function resolveCompanyLogo(): string
    {
        $template = config('workflow.company_logo_url');

        if (! $template) {
            return '';
        }

        return str_replace('{tenant}', (string) getTenant(), $template);
    }
}
