<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

use Taurus\Workflow\Consumer\Taurus\Helper;

/**
 * Used when the record fetched via `producersQuery` is the agency's producer
 * record, and the fields needed describe the specific agent tied to that
 * agency, reached through the `userAgent.agent` relation (as opposed to
 * AgencyProducer's own direct, agency-facing fields).
 */
class AgencyAgent  extends AbstractSchema
{
    /**
     * @var array
     *
     * This property holds the mapping of fields that are available to fetch.
     * It is an associative array where keys represent PLACEHOLDER and values
     * represent the corresponding data or configuration for those fields.
     *
     * Unlike AbstractSchema's lazy `getFieldMapping()` pattern, this is
     * populated eagerly in the constructor.
     */
    protected $fieldMapping = [];

    /**
     * @var string|null The name of the GraphQL query associated with this
     *                   class ('producersQuery').
     */
    protected $queryName;

    /**
     * @var string|null The jq path derived from $queryName (e.g. '.producerQuery').
     */
    protected $queryPath;

    public function __construct()
    {
        $this->queryName = 'producersQuery';
        $this->queryPath = '.'.$this->queryName;
        $this->fieldMapping = $this->initializeFieldMapping();
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the Agent.
     *
     * This method returns the associative array (already built in the
     * constructor) that maps the fields of the Agent, reached through
     * `userAgent.agent`, to their corresponding values or attributes.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the Agent.
     *
     * This method returns the name of the GraphQL query that can be used
     * to fetch data related to the Agent ('producerQuery').
     *
     * @return string The name of the GraphQL query for Agent.
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Initializes the field mapping with GraphQL schema for the Agent class.
     *
     * This method sets up the mapping of fields that can be fetched
     * from the GraphQL schema, all rooted at `userAgent.agent`. It is
     * called once during construction so all fields are mapped before
     * any operations are performed.
     *
     * KEYS are PLACEHOLDER for the GraphQL schema to be replaced.
     *
     * @return array
     */
    private function initializeFieldMapping(): array
    {
        $fieldMapping = [];

        $fieldMapping['FirstName'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'firstName' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agent.firstName",
        ];

        $fieldMapping['LastName'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'agencyName' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agent.agencyName",
        ];

        $fieldMapping['FloodCode'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'agencyFloodCode' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agent.agencyFloodCode",
        ];

        $fieldMapping['Status'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'agencyStatus' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agent.agencyStatus",
        ];

        $fieldMapping['Email'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'emailInfo' => [
                            'email' => null,
                        ],
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agent.emailInfo[0].email",
        ];

    
        $fieldMapping['PhoneNumber'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'phoneInfo' => [
                            'phoneNumber' => null,
                            'phoneTypeCode' => null,
                        ],
                        'addresses' => [
                            'phoneInfo' => [
                                'phoneNumber' => null,
                                'phoneTypeCode' => null,
                            ],
                        ],
                    ],
                ],
            ],
            'jqFilter' => "[{$this->queryPath}.userAgent.agent.phoneInfo[]?, {$this->queryPath}.userAgent.agent.addresses[]?.phoneInfo[]?] | map(select(type == \"object\" and .phoneTypeCode == \"Phone\")) | first | .phoneNumber",
            'parseResultCallback' => 'parsePhoneNumber',
        ];

        $mailingAddressStructure = [
            'addresses' => [
                'addressTypeCode' => null,
                'houseNo' => null,
                'streetName' => null,
                'addressLine1' => null,
                'addressLine2' => null,
                'addressLine3' => null,
                'addressLine4' => null,
                'postalCode' => null,
                'postalCodeSuffix' => null,
                'tbCity' => [
                    'name' => null,
                ],
                'tbState' => [
                    'name' => null,
                ],
            ],
        ];

        $fieldMapping['W9FormAddress'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        ...$mailingAddressStructure,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agent.addresses[] | select(.addressTypeCode == \"MAILING\")",
            'parseResultCallback' => 'parseW9FormAddress',
        ];

        $fieldMapping['W9FormCityStateZip'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        ...$mailingAddressStructure,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agent.addresses[] | select(.addressTypeCode == \"MAILING\")",
            'parseResultCallback' => 'parseW9FormCityStateZip',
        ];

        $fieldMapping['W9FormFeinSsnNo'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'feinSsnNo' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agent.feinSsnNo",
            'parseResultCallback' => 'parseW9FormFeinSsnNo',
        ];

        $fieldMapping['W9FormEmployeeIdentificationNumber'] = [
            'GraphQLschemaToReplace' => [
                'userAgent' => [
                    'agent' => [
                        'feinSsnNo' => null,
                    ],
                ],
            ],
            'jqFilter' => "{$this->queryPath}.userAgent.agent.feinSsnNo",
            'parseResultCallback' => 'parseW9FormEmployeeIdentificationNumber',
        ];

        return $this->wrapFieldMappingSchemaUnderData($fieldMapping);
    }

    public function parsePhoneNumber($phoneNumber)
    {
        return $phoneNumber ? Helper::formatPhone($phoneNumber) : $phoneNumber;
    }

    public function parseW9FormAddress($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $parts = array_filter(array_map('trim', [
            $addressArr['addressLine1'] ?? '',
            $addressArr['addressLine2'] ?? '',
        ]));

        return implode(', ', $parts) ?: null;
    }

    public function parseW9FormCityStateZip($addressArr)
    {
        if (empty($addressArr)) {
            return null;
        }

        $parts = array_filter(array_map('trim', [
            $addressArr['tbCity']['name'] ?? '',
            $addressArr['tbState']['name'] ?? '',
            $addressArr['postalCode'] ?? '',
        ]));

        return implode(', ', $parts) ?: null;
    }

    public function parseW9FormFeinSsnNo($feinSsnNo)
    {
        if (empty($feinSsnNo)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $feinSsnNo);

        // SSN format: XXX-XX-XXXX — each digit spaced
        if (strlen($digits) === 9) {
            $part1 = implode(' ', str_split(substr($digits, 0, 3)));
            $part2 = implode(' ', str_split(substr($digits, 3, 2)));
            $part3 = implode(' ', str_split(substr($digits, 5, 4)));

            return $part1.'    '.$part2.'   '.$part3;
        }

        return null;
    }

    public function parseW9FormEmployeeIdentificationNumber($einNumber)
    {
        if (empty($einNumber)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $einNumber);

        // Employee Identification Number format: XX-XXXXXXX — each digit spaced
        if (strlen($digits) === 9) {
            $part1 = implode(' ', str_split(substr($digits, 0, 2)));
            $part2 = implode(' ', str_split(substr($digits, 2, 7)));

            return $part1.'   '.$part2;
        }

        return null;
    }
}
