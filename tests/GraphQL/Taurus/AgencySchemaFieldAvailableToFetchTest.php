<?php

namespace Taurus\Workflow\Tests\GraphQL\Taurus;

use Orchestra\Testbench\TestCase;
use Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch\Agency;

class AgencySchemaFieldAvailableToFetchTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Mirrors AbstractSchema::getFieldMapping(), which wraps every non-empty
     * GraphQLschemaToReplace fragment under a 'data' key.
     */
    private function wrapSchemaWithData(array $schema): array
    {
        return empty($schema) ? $schema : ['data' => $schema];
    }

    // -------------------------------------------------------------------------
    // W9FormEmployeeIdentificationNumber field mapping tests
    // -------------------------------------------------------------------------

    public function test_field_mapping_for_w9_form_employee_identification_number()
    {
        $agencyProducer = new Agency;
        $fieldMapping = $agencyProducer->getFieldMapping();

        $expectedArray = [
            'GraphQLschemaToReplace' => $this->wrapSchemaWithData([
                'userAgent' => [
                    'agency' => [
                        'feinSsnNo' => null,
                    ],
                ],
            ]),
            'jqFilter' => '.producersQuery.userAgent.agency.feinSsnNo',
            'parseResultCallback' => 'parseW9FormEmployeeIdentificationNumber',
        ];

        $this->assertEquals($expectedArray, $fieldMapping['W9FormEmployeeIdentificationNumber']);
    }

    // -------------------------------------------------------------------------
    // parseW9FormEmployeeIdentificationNumber() tests
    // -------------------------------------------------------------------------

    public function test_parse_w9_form_employee_identification_number_formats_nine_digit_number()
    {
        $agencyProducer = new Agency;

        $this->assertSame('1 2   3 4 5 6 7 8 9', $agencyProducer->parseW9FormEmployeeIdentificationNumber('123456789'));
    }

    public function test_parse_w9_form_employee_identification_number_strips_non_digit_characters_before_formatting()
    {
        $agencyProducer = new Agency;

        $this->assertSame('1 2   3 4 5 6 7 8 9', $agencyProducer->parseW9FormEmployeeIdentificationNumber('12-3456789'));
    }

    public function test_parse_w9_form_employee_identification_number_returns_null_when_empty()
    {
        $agencyProducer = new Agency;

        $this->assertNull($agencyProducer->parseW9FormEmployeeIdentificationNumber(''));
    }

    public function test_parse_w9_form_employee_identification_number_returns_null_when_null()
    {
        $agencyProducer = new Agency;

        $this->assertNull($agencyProducer->parseW9FormEmployeeIdentificationNumber(null));
    }

    public function test_parse_w9_form_employee_identification_number_returns_null_when_digit_count_is_not_nine()
    {
        $agencyProducer = new Agency;

        $this->assertNull($agencyProducer->parseW9FormEmployeeIdentificationNumber('12345678'));
        $this->assertNull($agencyProducer->parseW9FormEmployeeIdentificationNumber('1234567890'));
    }
}
