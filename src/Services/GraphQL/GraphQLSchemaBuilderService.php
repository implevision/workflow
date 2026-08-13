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
    public function generateGraphQLQuery($data, $queryName, $variable = [], $queryArgs = [])
    {
        if (! empty($queryArgs)) {
            return $this->generateGraphQLQueryWithNamedArgs($data, $queryName, $queryArgs);
        }

        $fields = $this->arrayToGraphQLFields($data, 0);

        $variablesStr = $this->arrayToGraphQLWhereCondition($variable);

        // No usable condition -> query without a where clause instead of emitting
        // an invalid "where: " argument.
        $args = $variablesStr === '' ? '' : '(where: '.$variablesStr.')';

        return "query {\n  $queryName".$args."{\n".
            preg_replace('/^/m', '    ', $fields)."\n  }\n}";
    }

    /**
     * Generates a paginated GraphQL query (Lighthouse @paginate) from the same
     * where-condition and schema data the single-record query uses.
     *
     * The requested fields land inside `data`, and `paginatorInfo` is always asked
     * for so the caller can decide whether another page is due.
     *
     * @param  array  $data  The data structure built via addField()
     * @param  string  $queryName  Name of the paginated query, e.g. 'claims'
     * @param  array  $variable  Where-condition array (may be empty)
     * @param  int  $first  Records per page
     * @param  int  $page  Page number, 1-based as expected by @paginate
     * @return string Complete GraphQL query
     */
    public function generatePaginatedGraphQLQuery($data, $queryName, $variable = [], $first = 20, $page = 1)
    {
        $fields = $this->arrayToGraphQLFields($data, 0);

        $args = ['first: '.(int) $first, 'page: '.max(1, (int) $page)];

        $variablesStr = $this->arrayToGraphQLWhereCondition($variable);
        if ($variablesStr !== '') {
            $args[] = 'where: '.$variablesStr;
        }

        return "query {\n  $queryName(".implode(', ', $args)."){\n".
            "    data {\n".
            preg_replace('/^/m', '      ', $fields)."\n".
            "    }\n".
            "    paginatorInfo {\n      currentPage\n      lastPage\n      hasMorePages\n      total\n    }\n".
            "  }\n}";
    }

    /**
     * Generates a GraphQL query using named arguments (e.g. PolicyRenewal(date: "...", days: 15))
     * instead of a where: clause. Used for modules like Renewal that use custom query args.
     */
    public function generateGraphQLQueryWithNamedArgs(array $data, string $queryName, array $args): string
    {
        $fields = $this->arrayToGraphQLFields($data, 0);

        $argsStr = implode(', ', array_map(
            fn ($k, $v) => is_string($v) ? "$k: \"$v\"" : "$k: $v",
            array_keys($args),
            $args
        ));

        return "query {\n  $queryName($argsStr){\n".
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

            if (empty($children)) {
                return [];
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
    public static function extractRelationName(string $relation)
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
    public static function extractRelationColumn(string $relation)
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

        $str = str_replace('::', '', $str);

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
        // A JOIN carries extra conditions to merge with the base condition
        // (e.g. effective-action window + rules configured on the workflow).
        if (array_key_exists('JOIN', $cond)) {
            return $this->formatJoinedCondition($cond);
        }

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
        }

        // Anything without a column is not a usable rule (misconfigured condition).
        if (! isset($cond['column'])) {
            return '';
        }

        return sprintf(
            '{ column: %s, operator: %s, value: "%s" }',
            $cond['column'],
            $cond['operator'] ?? 'EQ',
            $cond['value'] ?? ''
        );
    }

    /**
     * Merges the base condition with everything held under its 'JOIN' key.
     *
     * The base can itself be a single rule (record identifier) or a group
     * (e.g. the WITH_IN date window), so it is formatted recursively instead of
     * assuming column/operator/value are present at the top level.
     *
     * @param  array  $variable  The condition array containing a 'JOIN' key
     */
    private function formatJoinedCondition(array $variable): string
    {
        $join = $variable['JOIN'];
        unset($variable['JOIN']);

        // JOIN may hold either { operator, condition: [...] } or a single condition.
        if (isset($join['condition']) && is_array($join['condition'])) {
            $joinOperator = strtoupper($join['operator'] ?? 'AND') === 'OR' ? 'OR' : 'AND';
            $joinConditions = $join['condition'];
        } else {
            $joinOperator = 'AND';
            $joinConditions = [$join];
        }

        $conditionStrs = [];
        if (! empty($variable)) {
            $conditionStrs[] = $this->formatGraphQLCondition($variable);
        }
        foreach ($joinConditions as $cond) {
            if (is_array($cond)) {
                $conditionStrs[] = $this->formatGraphQLCondition($cond);
            }
        }
        $conditionStrs = array_values(array_filter($conditionStrs));

        if (empty($conditionStrs)) {
            return '';
        }

        if (count($conditionStrs) === 1) {
            return $conditionStrs[0];
        }

        return sprintf('{ %s: [%s] }', $joinOperator, implode(', ', $conditionStrs));
    }

    /**
     * Formats a structured array into a GraphQL where condition string
     *
     * @param  mixed  $variable
     * @return string
     */
    public function arrayToGraphQLWhereCondition($variable)
    {
        if (! is_array($variable) || empty($variable)) {
            return '';
        }

        return $this->formatGraphQLCondition($variable);
    }
}
