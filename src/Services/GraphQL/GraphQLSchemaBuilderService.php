<?php

namespace Taurus\Workflow\Services\GraphQL;

class GraphQLSchemaBuilderService
{
    private $fieldMapping;

    private $graphQLSchema;

    public function __construct($fieldMapping)
    {
        $this->fieldMapping = $fieldMapping;
        $this->graphQLSchema = [];
    }

    public function getSchema()
    {
        return $this->graphQLSchema;
    }

    public function addKeys($target, $source)
    {
        foreach ($source as $key => $value) {
            // If key doesn't exist in target, add it
            if (! array_key_exists($key, $target)) {
                $target[$key] = $value;
            }
            // If both values are arrays, recursively merge them
            elseif (is_array($target[$key]) && is_array($value)) {
                $target[$key] = $this->addKeys($target[$key], $value);
            }
            // If key exists and values are not arrays, skip (don't overwrite)
        }

        return $target;
    }

    public function addField($placeholder)
    {
        if (array_key_exists($placeholder, $this->fieldMapping) && array_key_exists('GraphQLschemaToReplace', $this->fieldMapping[$placeholder])) {
            $this->graphQLSchema = $this->addKeys($this->graphQLSchema, $this->fieldMapping[$placeholder]['GraphQLschemaToReplace']);
        }
    }

    /**
     * Converts a multidimensional array into GraphQL field structure
     *
     * @param  array  $data  The multidimensional array to convert
     * @param  int  $indent  Current indentation level (for formatting)
     * @return string The GraphQL field structure
     */
    public function arrayToGraphQLFields($data, $indent = 0)
    {
        if (! is_array($data)) {
            return '';
        }

        $fields = [];
        $indentStr = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Check if it's an associative array or indexed array
                if (array_keys($value) === range(0, count($value) - 1)) {
                    // Indexed array - use first element as template
                    if (! empty($value) && is_array($value[0])) {
                        $nestedFields = $this->arrayToGraphQLFields($value[0], $indent + 1);
                        $fields[] = $indentStr.$key." {\n".$nestedFields."\n".$indentStr.'}';
                    } else {
                        // Simple array of scalars
                        $fields[] = $indentStr.$key;
                    }
                } else {
                    // Associative array
                    $nestedFields = $this->arrayToGraphQLFields($value, $indent + 1);
                    if ($nestedFields) {
                        $fields[] = $indentStr.$key." {\n".$nestedFields."\n".$indentStr.'}';
                    } else {
                        $fields[] = $indentStr.$key;
                    }
                }
            } else {
                // Scalar value
                $fields[] = $indentStr.$key;
            }
        }

        return implode("\n", $fields);
    }

    /**
     * Generates a complete GraphQL query from array structure
     *
     * @param  array  $data  The data structure
     * @param  string  $queryName  The name of the query
     * @param  array  $variables  Optional query variables
     * @return string Complete GraphQL query
     */
    public function generateGraphQLQuery($data, $queryName, $variables = [])
    {
        $fields = $this->arrayToGraphQLFields($data, 0);

        $variablesStr = implode('\n', $variables);

        return "query {\n  $queryName(where: {".$variablesStr."}){\n".
            preg_replace('/^/m', '    ', $fields)."\n  }\n}";
    }

    /**
     * Alternative function for generating field list only (without query wrapper)
     *
     * @param  array  $data  The data structure
     * @return string GraphQL fields without query wrapper
     */
    public function generateGraphQLFieldList($data)
    {
        return $this->arrayToGraphQLFields($data);
    }

    public static function getQueryMapping($column, $operator, $value)
    {
        if (! is_array($column)) {
            $column = strtoupper(self::convertToUnderscore($column));

            return '
            AND: [
                { column: '.$column.', operator: '.$operator.', value: '.$value.' }      
            ]';
        }
    }

    public static function convertToUnderscore($str)
    {
        if (empty($str)) {
            return $str;
        }

        $str = str_replace('_', '', $str);
        $result = '';

        // Handle first character separately (no underscore before it)
        $result .= $str[0];

        // Process remaining characters
        for ($i = 1; $i < strlen($str); $i++) {
            $char = $str[$i];

            // If character is uppercase, add underscore before it
            if (ctype_upper($char)) {
                $result .= '_'.$char;
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    public function extractValue($data, $jqFilter)
    {
        if (is_array($data)) {
            $json = json_encode($data);
        } else {
            $json = $data;
        }

        // Use jq to filter the JSON data
        $command = 'echo '.escapeshellarg($json).' | jq -r '.escapeshellarg($jqFilter);
        exec($command.' 2>&1', $result, $returnCode);

        if ($returnCode !== 0) {
            // echo "Command failed with return code: " . $returnCode;
            // echo "Error output: " . implode("\n", $result);
            return false;
        } else {
            return implode("\n", $result);
        }
    }
}
