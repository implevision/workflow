<?php

namespace Taurus\Workflow\Tests\GraphQL\Taurus;

use Orchestra\Testbench\TestCase;
use Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch\TbClaim;

class TbClaimSchemaFieldAvailableToFetchTest extends TestCase
{
    public function test_query_name()
    {

        $tbClaim = new TbClaim;
        $this->assertEquals('claim', $tbClaim->getQueryName(), "Query name should be 'claim'");
    }

    public function test_field_mapping()
    {
        $tbClaim = new TbClaim;
        $filedMapping = $tbClaim->getFieldMapping();

        // For claim ID
        $expectedArray = [
            'GraphQLschemaToReplace' => [
                'claimId' => null,
                'claimNumber' => null,
            ],
            'jqFilter' => '.claim.claimId',
        ];

        $this->assertEquals($filedMapping['claimId'], $expectedArray);
        // print_r($tbClaim->getFieldMapping());
    }
}
