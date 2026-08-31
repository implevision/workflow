<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

class TbQuotepolicy extends AbstractSchema
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

    /**
     * @var string|null The path of the query associated with this class.
     */
    protected $queryPath;

    public function __construct()
    {
        $this->queryName = 'quotesQuery';
        $this->queryPath = '.'.$this->queryName;
        $this->fieldMapping = $this->initializeFieldMapping();
        $this->setHeaders([
            'X-Request-Agent-Portal' => true,
        ]);
    }

    /**
     * Retrieves the field mapping with GraphQL schema for the TbQuotepolicy.
     *
     * This method returns an associative array that maps the fields
     * of the TbQuotepolicy to their corresponding values or attributes.
     *
     * @return array An associative array representing the field mapping.
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Retrieves the query name for the TbQuotepolicy.
     *
     * This method returns the name of the GraphQL query that can be used
     * to fetch data related to the TbQuotepolicy.
     *
     * @return string The name of the GraphQL query for TbQuotepolicy.
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Initializes the field mapping with GraphQL schema for the TbQuotepolicy class.
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
            'QuoteNo' => [
                'GraphQLschemaToReplace' => [
                    'quoteNo' => null,
                ],
                'jqFilter' => "{$this->queryPath}.quoteNo",
            ],
        ];

        return $this->wrapFieldMappingSchemaUnderData($fieldMapping);
    }
}
