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
     * Default: every generated query requests `paginatorInfo { hasMorePages }`
     * alongside `data`, so that flag directly tells us whether to advance to
     * the next page. Override in module schema classes that need a different
     * stopping condition.
     */
    public function getNextPageArgs(array $response, array $currentArgs): ?array
    {
        $hasMorePages = $response[$this->getQueryName()]['paginatorInfo']['hasMorePages'] ?? false;

        if (! $hasMorePages) {
            return null;
        }

        return array_merge($currentArgs, ['page' => ($currentArgs['page'] ?? 0) + 1]);
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
}
