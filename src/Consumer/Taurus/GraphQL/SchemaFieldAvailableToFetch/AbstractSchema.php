<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

class AbstractSchema
{
    protected $headers = [];

    protected int $page = 0;

    protected $appendedPlaceHolders = [];

    /**
     * Date/event context derived from the workflow's dateTimeInfo config.
     * Used by modules whose query takes named date args (e.g. PolicyRenewal).
     */
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

    /**
     * Sets the headers for the request.
     *
     * This method allows you to specify an array of headers that will be used
     * in the request. The headers should be provided as key-value pairs.
     *
     * @param  array  $headers  An associative array of headers to set.
     * @return void
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    /**
     * Retrieves the headers for the schema.
     *
     * This method is responsible for returning an array of headers
     * that are necessary for the schema's operation. The headers
     * may include authentication tokens, content types, or any
     * other relevant information required by the schema.
     *
     * @return array An associative array of headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Returns custom query arguments to pass directly to the root query.
     * Override in module schema classes when the query uses named args instead of where:.
     * Example: queryPolicyRenewal(frequency: 15, typeOfFrequency: "DAY", incidentEvent: "AFTER")
     *
     * @return array key => value pairs, e.g. ['frequency' => 15, 'typeOfFrequency' => 'DAY']
     */
    public function getQueryArgs(): array
    {
        return [];
    }

    /**
     * Whether this module's query returns a page of records
     * (`{queryName: {data: [...], paginatorInfo: {...}}}`) rather than a
     * single record directly under the query root. Controls whether the
     * generated query requests `paginatorInfo { hasMorePages }` — querying
     * for a field the underlying GraphQL schema doesn't define would fail.
     * Override to return false for single-record lookups.
     */
    public function supportsPagination(): bool
    {
        return true;
    }

    /**
     * Returns the query args for the next page, or null when there are no more pages.
     *
     * Default: when supportsPagination() is true and the query is generated via
     * GraphQLSchemaBuilderService::generateGraphQLQuery() (where: ...), the
     * builder requests `paginatorInfo { hasMorePages }` alongside `data`, so
     * that flag directly tells us whether to advance to the next page. Override
     * in module schema classes that need a different stopping condition.
     *
     * @param  array  $response  Raw GraphQL response from the current page
     * @param  array  $currentArgs  Query args used for the current page
     * @return array|null Args for next page, or null if this is the last page
     */
    public function getNextPageArgs(array $response, array $currentArgs): ?array
    {
        $hasMorePages = $response[$this->getQueryName()]['paginatorInfo']['hasMorePages'] ?? false;

        if (! $hasMorePages) {
            return null;
        }

        return array_merge($currentArgs, ['page' => ($currentArgs['page'] ?? 0) + 1]);
    }

    /**
     * Override in module schema classes to extract per-record data directly
     * from the raw GraphQL response, bypassing the jqFilter mechanism.
     *
     * Each returned item becomes one email/action payload.
     * Return [] to fall back to the default jqFilter-based extraction.
     *
     * @param  array  $response  Raw GraphQL response array
     * @return array e.g. [['AgentEmail'=>'...','renewalListData'=>[...]], ...]
     */
    public function hasCustomRecordExtraction(): bool
    {
        return false;
    }

    public function getRecordsFromResponse(array $response): array
    {
        return [];
    }

    /**
     * Builds loop rows for an {{#each}} placeholder from a GraphQL list result.
     *
     * Handles the shared boilerplate — decoding a JSON string, dropping
     * non-array entries, and reindexing — so each loop field only needs to
     * supply a mapper that turns one item into its row of placeholder values.
     *
     * @param  array|string  $items  Raw list (JSON string or array) from the jqFilter
     * @param  callable  $rowMapper  fn(array $item): array — returns one row's placeholders
     * @return array List of rows
     */
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

    /**
     * The query root wraps records under `data` now (`{queryName: {data: [...]}}`),
     * so every non-empty GraphQLschemaToReplace fragment needs to be nested
     * under a `data` key too. Call this at the end of initializeFieldMapping()
     * instead of nesting `data` by hand in every field.
     *
     * @param  array  $fieldMapping  Field mapping keyed by placeholder
     * @return array The same mapping with each non-empty GraphQLschemaToReplace wrapped under `data`
     */
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

    /**
     * Override in module schema classes that handle their own GraphQL fetch
     * and data parsing (e.g. modules with custom query args instead of where:).
     *
     * Return [] to fall back to the default jqFilter-based extraction.
     *
     * @param  mixed  $client  GraphQLClient instance
     * @param  mixed  $builder  GraphQLSchemaBuilderService instance
     * @param  array  $schemaData  Schema built from addField() calls
     * @param  array  $graphQLQuery  Where-condition query array
     * @return array Flat list of records, each becoming one action payload
     */
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
