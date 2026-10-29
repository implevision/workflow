<?php

namespace Taurus\Workflow\Tests\Consumer\Taurus;

use Taurus\Workflow\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('workflow.current_consumer', 'taurus');
    }

    // Mirrors AbstractSchema::wrapFieldMappingSchemaUnderData(): non-empty GraphQLschemaToReplace sits under 'data'.
    protected function wrapSchemaWithData(array $schema): array
    {
        return empty($schema) ? $schema : ['data' => $schema];
    }

    // Mirrors AbstractSchema::wrapFieldMappingSchemaUnderData() for a whole field mapping, written independently.
    protected function wrapFieldMappingSchemaUnderData(array $fieldMapping): array
    {
        return array_map(
            fn (array $mapping) => empty($mapping['GraphQLschemaToReplace'])
                ? $mapping
                : [...$mapping, 'GraphQLschemaToReplace' => $this->wrapSchemaWithData($mapping['GraphQLschemaToReplace'])],
            $fieldMapping
        );
    }
}
