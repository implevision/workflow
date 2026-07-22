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
    public function generateGraphQLQuery($data, $queryName, $variable = [])
    {
        $fields = $this->arrayToGraphQLFields($data, 0);

        $variablesStr = $this->arrayToGraphQLWhereCondition($variable);

        return "query {\n  $queryName(where: ".$variablesStr."){\n".
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

    public static function getQueryMapping($column, $operator, $value, $relation = null)
    {
        if (! is_array($column)) {
            $column = strtoupper(self::convertToUnderscore($column));

            $result = ['column' => $column, 'operator' => $operator, 'value' => $value];

            if ($relation) {
                $result['relation'] = $relation;
            }

            return $result;
        }
    }

    /**
     * Recursively converts a group/rule structure to GraphQL where condition
     *
     * @param  array  $group  The group or rule structure
     * @return array GraphQL where condition
     */
    public static function buildWhereConditionFromGroup($group)
    {
        if (! is_array($group)) {
            return [];
        }

        if (($group['type'] ?? null) === 'rule') {
            $relation = $group['relation'] ?? '';
            $relationName = self::extractRelationName($relation);
            $column = self::extractRelationColumn($relation);
            $operator = $group['comparator'] ?? null;
            $value = $group['expectedValue'] ?? null;

            if ($relationName) {
                return [
                    'relation' => $relationName,
                    'column' => strtoupper(self::convertToUnderscore($column)),
                    'operator' => $operator,
                    'value' => $value,
                ];
            }

            return [
                'column' => strtoupper(self::convertToUnderscore($column)),
                'operator' => $operator,
                'value' => $value,
            ];
        }

        if (($group['type'] ?? null) === 'group' && isset($group['children']) && is_array($group['children'])) {
            $operator = strtoupper($group['operator'] ?? 'AND');
            $children = [];
            foreach ($group['children'] as $child) {
                $childCondition = self::buildWhereConditionFromGroup($child);
                if (! empty($childCondition)) {
                    $children[] = $childCondition;
                }
            }

            return [
                'operator' => $operator,
                'condition' => $children,
            ];
        }

        return [];
    }

    /**
     * Extracts the relation name from a relation string in the format "relation@column".
     * - The part before "@" is the relation name (e.g. the infra model relation).
     *
     * @param  string  $relation  The relation string
     * @return string|null The relation name or null if not found
     */
    private static function extractRelationName(string $relation)
    {
        $relationParts = explode('@', trim($relation), 2);
        $relationName = isset($relationParts[0]) ? $relationParts[0] : null;

        return $relationName;
    }

    /**
     * Extracts the relation column from a relation string in the format "relation@column".
     * - The part after "@" is the column/field within that relation to apply the condition to.
     *
     * @param  string  $relation  The relation string
     * @return string The relation column or empty string if not found
     */
    private static function extractRelationColumn(string $relation)
    {
        $relationParts = explode('@', trim($relation), 2);
        $relationColumn = isset($relationParts[1]) ? $relationParts[1] : '';

        return $relationColumn;
    }

    /**
     * Converts a string to uppercase with underscores (e.g. "fieldName" to "FIELD_NAME")
     *
     * @param  string  $str  The input string
     * @return string The converted string
     */
    public static function convertToUnderscore($str)
    {
        if (empty($str)) {
            return $str;
        }

        // Split by underscores, then process each segment
        $segments = explode('_', $str);
        $convertedSegments = [];
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }
            // Insert underscores before uppercase letters (except first letter), then uppercase all
            $converted = preg_replace('/([A-Z])/', '_$1', ucfirst($segment));
            $convertedSegments[] = strtoupper(ltrim($converted, '_'));
        }

        return implode('_', $convertedSegments);
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

    /**
     * Formats a GraphQL condition from a structured array
     * - Checks for nested conditions and relations
     * - Checks for 'JOIN' operator
     *
     * @param  array  $cond  The condition array
     */
    private function formatGraphQLCondition(array $cond): string
    {
        if (is_array($cond) && isset($cond['operator']) && isset($cond['condition'])) {
            $operator = $cond['operator'] === 'OR' ? 'OR' : 'AND';
            $childStrs = [];
            foreach ($cond['condition'] as $child) {
                $childStrs[] = $this->formatGraphQLCondition($child);
            }

            return sprintf('{ %s: [%s] }', $operator, implode(', ', $childStrs));
        }

        if (isset($cond['relation'])) {
            return sprintf(
                '{ HAS: { relation: "%s", condition: { column: %s, operator: %s, value: "%s" } } }',
                $cond['relation'],
                $cond['column'],
                $cond['operator'],
                $cond['value']
            );
        } else {
            return sprintf(
                '{ column: %s, operator: %s, value: "%s" }',
                $cond['column'],
                $cond['operator'],
                $cond['value']
            );
        }
    }

    /**
     * Formats a structured array into a GraphQL where condition string
     *
     * @param  mixed  $variable
     * @return string
     */
    public function arrayToGraphQLWhereCondition($variable)
    {
        if (array_key_exists('JOIN', $variable)) {
            $joinOperator = $variable['JOIN']['operator'];
            $joinConditions = $variable['JOIN']['condition'];
            $conditionStrs = [];
            foreach ($joinConditions as $cond) {
                $conditionStrs[] = $this->formatGraphQLCondition($cond);
            }
            $variablesStr = sprintf(
                '{ column: %s, operator: %s, value: "%s", %s: [%s] }',
                $variable['column'],
                $variable['operator'],
                $variable['value'],
                $joinOperator,
                implode(', ', $conditionStrs)
            );
        } else {
            $variablesStr = $this->formatGraphQLCondition($variable);
        }

        return $variablesStr;
    }
}
