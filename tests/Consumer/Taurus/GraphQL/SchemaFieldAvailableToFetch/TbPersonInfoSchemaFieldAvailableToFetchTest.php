<?php

namespace Taurus\Workflow\Tests\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch\TbPersonInfo;
use Taurus\Workflow\Tests\Consumer\Taurus\TestCase;

class TbPersonInfoSchemaFieldAvailableToFetchTest extends TestCase
{
    public function test_query_name()
    {
        $tbPersonInfo = new TbPersonInfo;
        $this->assertEquals('producersQuery', $tbPersonInfo->getQueryName(), "Query name should be 'producersQuery'");
    }

    public function test_field_mapping()
    {
        $tbPersonInfo = new TbPersonInfo;
        $fieldMapping = $tbPersonInfo->getFieldMapping();

        $expectedArray = $this->wrapFieldMappingSchemaUnderData([
            'W9FormEmployeeIdentificationNumber' => [
                'GraphQLschemaToReplace' => [
                    'userAgent' => [
                        'agency' => [
                            'feinSsnNo' => null,
                        ],
                    ],
                ],
                'jqFilter' => '.producersQuery.userAgent.agency.feinSsnNo',
                'parseResultCallback' => 'parseW9FormEmployeeIdentificationNumber',
            ],
        ]);

        $this->assertEquals($expectedArray['W9FormEmployeeIdentificationNumber'], $fieldMapping['W9FormEmployeeIdentificationNumber']);
    }

    // -------------------------------------------------------------------------
    // parseW9FormEmployeeIdentificationNumber() tests
    // -------------------------------------------------------------------------

    public function test_parse_w9_form_employee_identification_number_formats_nine_digit_number()
    {
        $tbPersonInfo = new TbPersonInfo;

        $this->assertSame('1 2   3 4 5 6 7 8 9', $tbPersonInfo->parseW9FormEmployeeIdentificationNumber('123456789'));
    }

    public function test_parse_w9_form_employee_identification_number_strips_non_digit_characters_before_formatting()
    {
        $tbPersonInfo = new TbPersonInfo;

        $this->assertSame('1 2   3 4 5 6 7 8 9', $tbPersonInfo->parseW9FormEmployeeIdentificationNumber('12-3456789'));
    }

    public function test_parse_w9_form_employee_identification_number_returns_null_when_empty()
    {
        $tbPersonInfo = new TbPersonInfo;

        $this->assertNull($tbPersonInfo->parseW9FormEmployeeIdentificationNumber(''));
    }

    public function test_parse_w9_form_employee_identification_number_returns_null_when_null()
    {
        $tbPersonInfo = new TbPersonInfo;

        $this->assertNull($tbPersonInfo->parseW9FormEmployeeIdentificationNumber(null));
    }

    public function test_parse_w9_form_employee_identification_number_returns_null_when_digit_count_is_not_nine()
    {
        $tbPersonInfo = new TbPersonInfo;

        $this->assertNull($tbPersonInfo->parseW9FormEmployeeIdentificationNumber('12345678'));
        $this->assertNull($tbPersonInfo->parseW9FormEmployeeIdentificationNumber('1234567890'));
    }
}
