<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

class AbstractSchema
{
    protected $headers = [];

    protected int $page = 1;

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
     * Example: PolicyRenewal(date: "2025-10-29", days: 15)
     *
     * @return array key => value pairs, e.g. ['date' => '2025-10-29', 'days' => 15]
     */
    public function getQueryArgs(): array
    {
        return [];
    }


    /**
     * Override in paginated module schema classes to return the query args for the next page.
     * Return null when there are no more pages (default — no pagination).
     *
     * @param  array  $response     Raw GraphQL response from the current page
     * @param  array  $currentArgs  Query args used for the current page
     * @return array|null  Args for next page, or null if this is the last page
     */
    public function getNextPageArgs(array $response, array $currentArgs): ?array
    {
        return null;
    }

    /**
     * Override in module schema classes to extract per-record data directly
     * from the raw GraphQL response, bypassing the jqFilter mechanism.
     *
     * Each returned item becomes one email/action payload.
     * Return [] to fall back to the default jqFilter-based extraction.
     *
     * @param  array  $response  Raw GraphQL response array
     * @return array  e.g. [['AgentEmail'=>'...','renewalListData'=>[...]], ...]
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
     * Override in module schema classes that handle their own GraphQL fetch
     * and data parsing (e.g. modules with custom query args instead of where:).
     *
     * Return [] to fall back to the default jqFilter-based extraction.
     *
     * @param  mixed  $client        GraphQLClient instance
     * @param  mixed  $builder       GraphQLSchemaBuilderService instance
     * @param  array  $schemaData    Schema built from addField() calls
     * @param  array  $graphQLQuery  Where-condition query array
     * @return array  Flat list of records, each becoming one action payload
     */
    public function fetchAllData($client, $builder, array $schemaData, array $graphQLQuery): array
    {
        return [];
    }
}
