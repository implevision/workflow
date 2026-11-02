<?php

namespace Taurus\Workflow\Tests\Support;

use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

// Real query building; extractValue() resolves plain `.a.b` filters in PHP, mirroring `jq -r`, instead of shelling out to jq.
class FakeSchemaBuilder extends GraphQLSchemaBuilderService
{
    public function extractValue($data, $jqFilter)
    {
        $value = data_get($data, ltrim($jqFilter, '.'));

        return match (true) {
            $value === null => 'null',
            is_string($value) => $value,
            default => json_encode($value),
        };
    }
}
