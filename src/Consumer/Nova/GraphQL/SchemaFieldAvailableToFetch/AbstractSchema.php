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

    public function fetchAllData($client, $builder, array $schemaData, array $graphQLQuery): array
    {
        return [];
    }
}
