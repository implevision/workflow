<?php

namespace Taurus\Workflow\Tests\GraphQL\Taurus;

use Avatar\Infrastructure\Models\Api\v1\TbPolicy;
use Avatar\Infrastructure\Models\Api\v1\TbProduct;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;
use Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch\TbClaim;

class TbClaimSchemaFieldAvailableToFetchTest extends TestCase
{
    protected function tearDown(): void
    {
        TbPolicy::reset();
        TbProduct::reset();
        parent::tearDown();
    }

    public function test_query_name()
    {
        $tbClaim = new TbClaim;
        $this->assertEquals('claim', $tbClaim->getQueryName(), "Query name should be 'claim'");
    }

    public function test_field_mapping()
    {
        $tbClaim = new TbClaim;
        $fieldMapping = $tbClaim->getFieldMapping();

        // For claim ID
        $expectedArray = [
            'GraphQLschemaToReplace' => [
                'claimId' => null,
            ],
            'jqFilter' => '.claim.ClaimId',
        ];

        $this->assertEquals($expectedArray, $fieldMapping['ClaimId']);
    }

    // -------------------------------------------------------------------------
    // parseCompanyName() tests
    // -------------------------------------------------------------------------

    public function test_parse_company_name_returns_branded_company_name_from_list()
    {
        $tbClaim = new TbClaim;

        $response = [
            'agency' => [
                'brandedCompany' => [
                    ['company' => ['companyName' => 'Acme Insurance']],
                ],
            ],
            'policyId' => null,
        ];

        $this->assertSame('Acme Insurance', $tbClaim->parseCompanyName($response));
    }

    public function test_parse_company_name_returns_branded_company_name_when_already_normalised()
    {
        $tbClaim = new TbClaim;

        // brandedCompany already has a 'company' key → treated as normalised by extractClaimContext
        $response = [
            'agency' => [
                'brandedCompany' => ['company' => ['companyName' => 'Direct Format Co']],
            ],
            'policyId' => null,
        ];

        $this->assertSame('Direct Format Co', $tbClaim->parseCompanyName($response));
    }

    public function test_parse_company_name_falls_back_to_product_holding_company()
    {
        // Arrange: policy 100 → product 200 → holding company 5 → WYO name
        TbPolicy::$findMap[100] = (object) ['n_ProductId_FK' => 200];
        TbProduct::$findMap[200] = (object) ['holding_company_id' => 5];

        $this->mockHoldingCompanyQuery($this->makeHoldingCompanyRecord([
            's_HoldingCompanyName' => 'Product WYO Co',
        ]));

        $tbClaim = new TbClaim;
        $response = [
            'agency' => ['brandedCompany' => []],
            'policyId' => 100,
        ];

        $this->assertSame('Product WYO Co', $tbClaim->parseCompanyName($response));
    }

    public function test_parse_company_name_falls_back_to_default_holding_company_when_policy_id_absent()
    {
        // No policyId → skip policy/product lookup → query first holding company
        $this->mockHoldingCompanyQuery($this->makeHoldingCompanyRecord([
            's_HoldingCompanyName' => 'Default WYO Co',
        ]));

        $tbClaim = new TbClaim;
        $response = [
            'agency' => ['brandedCompany' => []],
            'policyId' => null,
        ];

        $this->assertSame('Default WYO Co', $tbClaim->parseCompanyName($response));
    }

    public function test_parse_company_name_falls_back_to_default_holding_company_when_policy_not_found()
    {
        // policyId present but TbPolicy::find returns null (policy not in DB)
        TbPolicy::$findMap[999] = null;

        $this->mockHoldingCompanyQuery($this->makeHoldingCompanyRecord([
            's_HoldingCompanyName' => 'Default WYO Co',
        ]));

        $tbClaim = new TbClaim;
        $response = [
            'agency' => ['brandedCompany' => []],
            'policyId' => 999,
        ];

        $this->assertSame('Default WYO Co', $tbClaim->parseCompanyName($response));
    }

    // -------------------------------------------------------------------------
    // resolveCompanyLogoUrl() tests
    // -------------------------------------------------------------------------

    public function test_resolve_company_logo_url_returns_branded_company_public_logo()
    {
        $tbClaim = new TbClaim;

        $response = [
            'agency' => [
                'brandedCompany' => [
                    ['company' => ['logo' => null, 'publicLogo' => 'https://cdn.example.com/brand-logo.png']],
                ],
            ],
            'policyId' => null,
        ];

        $this->assertSame('https://cdn.example.com/brand-logo.png', $tbClaim->resolveCompanyLogoUrl($response));
    }

    public function test_resolve_company_logo_url_falls_back_to_product_holding_company_logo()
    {
        // Arrange: policy 100 → product 200 → holding company 5 → public logo
        TbPolicy::$findMap[100] = (object) ['n_ProductId_FK' => 200];
        TbProduct::$findMap[200] = (object) ['holding_company_id' => 5];

        $this->mockHoldingCompanyQuery($this->makeHoldingCompanyRecord([
            'public_logo_url' => 'https://cdn.example.com/hc-logo.png',
        ]));

        $tbClaim = new TbClaim;
        $response = [
            'agency' => ['brandedCompany' => []],
            'policyId' => 100,
        ];

        $this->assertSame('https://cdn.example.com/hc-logo.png', $tbClaim->resolveCompanyLogoUrl($response));
    }

    public function test_resolve_company_logo_url_falls_back_to_default_holding_company_logo_when_no_policy_id()
    {
        // No policyId → skip product lookup → default holding company
        $this->mockHoldingCompanyQuery($this->makeHoldingCompanyRecord([
            'public_logo_url' => 'https://cdn.example.com/default-logo.png',
        ]));

        $tbClaim = new TbClaim;
        $response = [
            'agency' => ['brandedCompany' => []],
            'policyId' => null,
        ];

        $this->assertSame('https://cdn.example.com/default-logo.png', $tbClaim->resolveCompanyLogoUrl($response));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Mock the DB query chain for the tb_holdingcompanies table, returning the
     * given $record from any ->first() call (with or without a preceding ->where()).
     */
    private function mockHoldingCompanyQuery(object $record): void
    {
        $queryBuilder = \Mockery::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn($record);
        DB::shouldReceive('table')->with('tb_holdingcompanies')->andReturn($queryBuilder);
    }

    /**
     * Build a fake holding-company DB record with sensible defaults.
     *
     * Note: the 'payment_wesite_url' key intentionally preserves the typo
     * found in the actual DB column name accessed by Helper::getHoldingCompanyDetail().
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeHoldingCompanyRecord(array $overrides = []): object
    {
        return (object) array_merge([
            'metadata' => '{}',
            'logo_url' => null,
            'public_logo_url' => null,
            's_HoldingCompanyName' => null,
            'naic_number' => '12345',
            'payment_wesite_url' => 'https://portal.example.com',
        ], $overrides);
    }
}
