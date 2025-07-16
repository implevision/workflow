<?php

namespace Taurus\Workflow\Tests\GraphQL\Taurus;

use Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch\TbClaim;
use Orchestra\Testbench\TestCase;


class TbClaimSchemaFieldAvailableToFetchTest extends TestCase
{
    public function testQueryName()
    {

        $tbClaim = new TbClaim();
        $this->assertEquals('claim', $tbClaim->getQueryName(), "Query name should be 'claim'");
    }

    public function testFieldMapping()
    {
        $tbClaim = new TbClaim();
        $filedMapping = $tbClaim->getFieldMapping();

        //For claim ID
        $expectedArray = [
            'GraphQLschemaToReplace' => [
                'claimId' => null,
                'claimNumber' => null
            ],
            'jqFilter' => '.claim.claimId'
        ];

        $this->assertEquals($filedMapping['claimId'], $expectedArray);
        //print_r($tbClaim->getFieldMapping());
    }
}
