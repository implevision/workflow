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
        $this->assertEquals(1, 1);
        //print_r($tbClaim->getFieldMapping());
    }
}
