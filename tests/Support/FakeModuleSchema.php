<?php

namespace Taurus\Workflow\Tests\Support;

use Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch\AbstractSchema;

class FakeModuleSchema extends AbstractSchema
{
    public const QUERY_NAME = 'policiesQuery';

    public bool $paginated = true;

    public ?array $customRecords = null;

    public array $requiredPlaceholdersForCreateRecord = [];

    public array $fieldMapping;

    public function __construct()
    {
        $this->fieldMapping = [
            'PolicyNumber' => self::field('policyNumber'),
            'FirstName' => self::field('firstName'),
            'AgentEmail' => self::field('agentEmail'),
            'InsuredName' => self::field('insuredName', 'formatName'),
            'Tags' => self::field('tags'),
            'Today' => ['GraphQLschemaToReplace' => [], 'jqFilter' => '', 'parseResultCallback' => 'today'],
            'Broken' => ['GraphQLschemaToReplace' => [], 'jqFilter' => '', 'parseResultCallback' => 'missingMethod'],
        ];
    }

    public static function field(string $name, ?string $parseResultCallback = null): array
    {
        return array_filter([
            'GraphQLschemaToReplace' => ['data' => [$name => null]],
            'jqFilter' => '.'.self::QUERY_NAME.'.'.$name,
            'parseResultCallback' => $parseResultCallback,
        ]);
    }

    public function getFieldMapping(): array
    {
        return $this->fieldMapping;
    }

    public function getQueryName(): string
    {
        return self::QUERY_NAME;
    }

    public function supportsPagination(): bool
    {
        return $this->paginated;
    }

    public function hasCustomRecordExtraction(): bool
    {
        return $this->customRecords !== null;
    }

    public function getRecordsFromResponse(array $response): array
    {
        return $this->customRecords ?? [];
    }

    public function getRequiredPlaceholdersForCreateRecord(): array
    {
        return $this->requiredPlaceholdersForCreateRecord;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function formatName($value = null): string
    {
        return 'Formatted: '.$value;
    }

    public function today(): string
    {
        return '2026-01-01';
    }
}
