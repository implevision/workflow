<?php

namespace Taurus\Workflow\Tests\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch\TbQuotepolicy;
use Taurus\Workflow\Tests\Consumer\Taurus\TestCase;

class TbQuotepolicySchemaFieldAvailableToFetchTest extends TestCase
{
    public function test_query_name()
    {
        $this->assertEquals('quotesQuery', (new TbQuotepolicy)->getQueryName());
    }

    public function test_field_mapping()
    {
        $expectedArray = $this->wrapFieldMappingSchemaUnderData([
            'QuoteNo' => [
                'GraphQLschemaToReplace' => [
                    'quoteNo' => null,
                ],
                'jqFilter' => '.quotesQuery.quoteNo',
            ],
            'ApplicantEmail' => [
                'GraphQLschemaToReplace' => [
                    'transaction' => [
                        'ownerEmail' => [
                            'email' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.quotesQuery.transaction?.ownerEmail?.email?',
            ],
        ]);

        $this->assertEquals($expectedArray, (new TbQuotepolicy)->getFieldMapping());
    }
}
