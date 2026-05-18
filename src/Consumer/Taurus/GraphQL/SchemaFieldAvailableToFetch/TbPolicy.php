<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

class TbPolicy
{
    /**
     * @var array
     *
     * This property holds the mapping of fields that are available to fetch.
     * It is an associative array where keys represent PLACEHOLDER and values
     * represent the corresponding data or configuration for those fields.
     */
    protected $fieldMapping = [];

    /**
     * @var string|null The name of the query associated with this class.
     */
    protected $queryName;

    public function __construct()
    {
        $this->fieldMapping = $this->initializeFieldMapping();
        $this->queryName = 'policy';
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the TbPolicy.
     *
     * This method returns an associative array that maps the fields
     * of the TbPolicy to their corresponding values or attributes.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the TbPolicy.
     *
     * This method returns the name of the GraphQL query that can be used
     * to fetch data related to the TbPolicy.
     *
     * @return string The name of the GraphQL query for TbPolicy.
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Initializes the field mapping with GraphQL schema for the TbPolicy class.
     *
     * This method sets up the mapping of fields that can be fetched
     * from the GraphQL schema. It is called during the initialization
     * phase of the class to ensure that all fields are properly mapped
     * before any operations are performed.
     *
     * KEYS are PLACEHOLDER for the GraphQL schema to be replaced.
     *
     * @return array
     */
    private function initializeFieldMapping()
    {
        $fieldMapping = [
            'PolicyNumber' => [
                'GraphQLschemaToReplace' => [
                    'policyNumber' => null,
                ],
                'jqFilter' => '.data.policy.policy.policyNumber',
            ],

            'Document' => [
                'GraphQLschemaToReplace' => [
                    'docurl' => null,
                ],
                // This finds the correct DECLARATION document,
                // then extracts the first docInfo.docurl value.
                'jqFilter' => '
                   [
                         .data.policy.policy.docuploadinfo[]
                         | select(.doctypes.docTypeCode == "DECLARATION")
                         | .docUploadDocInfoRel[]
                         | .docInfo[]
                         | .docurl
                  ]
                ',
            ],
        ];

        return $fieldMapping;
    }
}
