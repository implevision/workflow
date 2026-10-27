<?php

namespace Taurus\Workflow\Tests\Services\GraphQL;

use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;
use Taurus\Workflow\Tests\TestCase;

class GraphQLSchemaBuilderServiceTest extends TestCase
{
    private const FIELD_MAPPING = [
        'PolicyNumber' => ['GraphQLschemaToReplace' => ['data' => ['policyNumber' => null]]],
        'AgentName' => ['GraphQLschemaToReplace' => ['data' => ['agent' => ['name' => null]]]],
        'AgentEmail' => ['GraphQLschemaToReplace' => ['data' => ['agent' => ['email' => null, 'name' => 'ignored']]]],
        'Documents' => ['GraphQLschemaToReplace' => ['data' => ['docuploadinfo' => [['fileName' => null]]]]],
        'Computed' => ['jqFilter' => ''],
    ];

    private function builder(): GraphQLSchemaBuilderService
    {
        return new GraphQLSchemaBuilderService(self::FIELD_MAPPING);
    }

    private function rule(string $column, string $value, ?string $relation = null): array
    {
        return array_filter(['relation' => $relation, 'column' => $column, 'operator' => 'EQ', 'value' => $value]);
    }

    public function test_add_field_merges_each_placeholder_schema_without_overwriting(): void
    {
        $builder = $this->builder();

        foreach (['PolicyNumber', 'AgentName', 'AgentEmail'] as $placeholder) {
            $builder->addField($placeholder);
        }

        $this->assertSame(['data' => ['policyNumber' => null, 'agent' => ['name' => null, 'email' => null]]], $builder->getSchema());
    }

    public function test_add_field_ignores_unmapped_placeholders_and_mappings_without_a_schema(): void
    {
        $builder = $this->builder();

        $builder->addField('Unknown');
        $builder->addField('Computed');

        $this->assertSame([], $builder->getSchema());
    }

    public function test_array_to_graphql_fields_renders_nested_and_list_fields(): void
    {
        $fields = $this->builder()->arrayToGraphQLFields([
            'policyNumber' => null,
            'agent' => ['name' => null],
            'docuploadinfo' => [['fileName' => null]],
            'tags' => ['gold', 'auto'],
            'empty' => [],
        ]);

        $this->assertSame("policyNumber\nagent {\n  name\n}\ndocuploadinfo {\n  fileName\n}\ntags\nempty", $fields);
    }

    public function test_array_to_graphql_fields_returns_empty_for_non_arrays(): void
    {
        $this->assertSame('', $this->builder()->arrayToGraphQLFields('policyNumber'));
    }

    public function test_generate_graphql_query_requests_the_next_one_based_page_with_paginator_info(): void
    {
        $query = $this->builder()->generateGraphQLQuery(['data' => ['policyNumber' => null]], 'policiesQuery', $this->rule('POLICY_ID', '7'), [], 2);

        $this->assertSame(
            "query {\n  policiesQuery(where: { column: POLICY_ID, operator: EQ, value: \"7\" } page: 3){\n".
            "    data {\n      policyNumber\n    }\n    paginatorInfo {\n      hasMorePages\n    }\n  }\n}",
            $query
        );
    }

    public function test_generate_graphql_query_without_pagination(): void
    {
        $query = $this->builder()->generateGraphQLQuery(['policyNumber' => null], 'policyQuery', $this->rule('POLICY_ID', '7'), [], 2, false);

        $this->assertSame("query {\n  policyQuery(where: { column: POLICY_ID, operator: EQ, value: \"7\" }){\n    policyNumber\n  }\n}", $query);
    }

    public function test_generate_graphql_query_uses_named_args_instead_of_where_when_given(): void
    {
        $query = $this->builder()->generateGraphQLQuery(['policyNumber' => null], 'PolicyRenewal', $this->rule('POLICY_ID', '7'), ['date' => '2026-01-31', 'days' => 15]);

        $this->assertSame("query {\n  PolicyRenewal(date: \"2026-01-31\", days: 15){\n    policyNumber\n  }\n}", $query);
    }

    public function test_where_condition_renders_a_single_rule(): void
    {
        $this->assertSame('{ column: STATUS, operator: EQ, value: "ACTIVE" }', $this->builder()->arrayToGraphQLWhereCondition($this->rule('STATUS', 'ACTIVE')));
    }

    public function test_where_condition_renders_relations_and_nested_groups(): void
    {
        $where = ['operator' => 'OR', 'condition' => [
            $this->rule('STATUS', 'ACTIVE'),
            ['operator' => 'AND', 'condition' => [$this->rule('DOC_TYPE', 'ID', 'TbDocuploadinfo')]],
        ]];

        $this->assertSame(
            '{ OR: [{ column: STATUS, operator: EQ, value: "ACTIVE" }, { AND: [{ HAS: { relation: "TbDocuploadinfo", condition: { column: DOC_TYPE, operator: EQ, value: "ID" } } }] }] }',
            $this->builder()->arrayToGraphQLWhereCondition($where)
        );
    }

    public function test_where_condition_joins_extra_conditions_onto_the_root_rule(): void
    {
        $where = [...$this->rule('POLICY_ID', '7'), 'JOIN' => ['operator' => 'AND', 'condition' => [$this->rule('STATUS', 'ACTIVE')]]];

        $this->assertSame(
            '{ column: POLICY_ID, operator: EQ, value: "7", AND: [{ column: STATUS, operator: EQ, value: "ACTIVE" }] }',
            $this->builder()->arrayToGraphQLWhereCondition($where)
        );
    }

    public function test_apply_relation_filters_mirrors_anded_has_conditions_onto_the_relation_field(): void
    {
        $schema = ['data' => ['policyNumber' => null, 'docuploadinfo' => ['fileName' => null]]];
        $where = ['operator' => 'AND', 'condition' => [$this->rule('STATUS', 'ACTIVE'), $this->rule('DOC_TYPE', 'ID', 'TbDocuploadinfo')]];

        $filtered = $this->builder()->applyRelationFilters($schema, $where, ['TbDocuploadinfo' => 'docuploadinfo']);

        $this->assertSame(
            ['data' => ['policyNumber' => null, 'docuploadinfo(where: { column: DOC_TYPE, operator: EQ, value: "ID" })' => ['fileName' => null]]],
            $filtered
        );
    }

    public function test_apply_relation_filters_ors_several_has_conditions_on_the_same_field(): void
    {
        $where = ['operator' => 'AND', 'condition' => [
            $this->rule('DOC_TYPE', 'ID', 'TbDocuploadinfo'),
            $this->rule('DOC_TYPE', 'PHOTO', 'TbDocuploadinfo'),
        ]];

        $filtered = $this->builder()->applyRelationFilters(['docuploadinfo' => ['fileName' => null]], $where, ['TbDocuploadinfo' => 'docuploadinfo']);

        $this->assertSame(
            ['docuploadinfo(where: { OR: [{ column: DOC_TYPE, operator: EQ, value: "ID" }, { column: DOC_TYPE, operator: EQ, value: "PHOTO" }] })' => ['fileName' => null]],
            $filtered
        );
    }

    public function test_apply_relation_filters_follows_joined_conditions(): void
    {
        $where = [...$this->rule('POLICY_ID', '7'), 'JOIN' => ['operator' => 'AND', 'condition' => [$this->rule('DOC_TYPE', 'ID', 'TbDocuploadinfo')]]];

        $filtered = $this->builder()->applyRelationFilters(['docuploadinfo' => ['fileName' => null]], $where, ['TbDocuploadinfo' => 'docuploadinfo']);

        $this->assertSame(['docuploadinfo(where: { column: DOC_TYPE, operator: EQ, value: "ID" })' => ['fileName' => null]], $filtered);
    }

    public function test_apply_relation_filters_skips_has_conditions_under_an_or(): void
    {
        $schema = ['docuploadinfo' => ['fileName' => null]];
        $where = ['operator' => 'OR', 'condition' => [$this->rule('STATUS', 'ACTIVE'), $this->rule('DOC_TYPE', 'ID', 'TbDocuploadinfo')]];

        $this->assertSame($schema, $this->builder()->applyRelationFilters($schema, $where, ['TbDocuploadinfo' => 'docuploadinfo']));
    }

    public function test_apply_relation_filters_ignores_relations_without_a_mapped_field(): void
    {
        $schema = ['docuploadinfo' => ['fileName' => null]];

        $this->assertSame($schema, $this->builder()->applyRelationFilters($schema, $this->rule('DOC_TYPE', 'ID', 'TbDocuploadinfo'), []));
    }

    public function test_build_where_condition_converts_a_rule_group(): void
    {
        $group = ['type' => 'group', 'operator' => 'or', 'children' => [
            ['type' => 'rule', 'relation' => '@policyStatus', 'comparator' => 'EQ', 'expectedValue' => 'ACTIVE'],
            ['type' => 'rule', 'relation' => 'TbDocuploadinfo@docType', 'comparator' => 'EQ', 'expectedValue' => 'ID'],
            ['type' => 'group', 'operator' => 'AND', 'children' => []],
        ]];

        $this->assertSame(['operator' => 'OR', 'condition' => [
            ['column' => 'POLICY_STATUS', 'operator' => 'EQ', 'value' => 'ACTIVE'],
            ['relation' => 'TbDocuploadinfo', 'column' => 'DOC_TYPE', 'operator' => 'EQ', 'value' => 'ID'],
        ]], GraphQLSchemaBuilderService::buildWhereConditionFromGroup($group));
    }

    public function test_build_where_condition_returns_empty_for_empty_or_unknown_input(): void
    {
        $this->assertSame([], GraphQLSchemaBuilderService::buildWhereConditionFromGroup('rule'));
        $this->assertSame([], GraphQLSchemaBuilderService::buildWhereConditionFromGroup(['type' => 'other']));
        $this->assertSame([], GraphQLSchemaBuilderService::buildWhereConditionFromGroup(['type' => 'group', 'children' => []]));
    }

    public function test_get_query_mapping_converts_the_column_and_keeps_the_relation(): void
    {
        $this->assertSame(['column' => 'POLICY_ID', 'operator' => 'EQ', 'value' => 7], GraphQLSchemaBuilderService::getQueryMapping('policyId', 'EQ', 7));
        $this->assertSame(
            ['column' => 'DOC_TYPE', 'operator' => 'EQ', 'value' => 'ID', 'relation' => 'TbDocuploadinfo'],
            GraphQLSchemaBuilderService::getQueryMapping('docType', 'EQ', 'ID', 'TbDocuploadinfo')
        );
        $this->assertNull(GraphQLSchemaBuilderService::getQueryMapping(['policyId'], 'EQ', 7));
    }

    public function test_relation_name_and_column_are_split_on_the_at_sign(): void
    {
        $this->assertSame(['TbDocuploadinfo', 'docType'], [
            GraphQLSchemaBuilderService::extractRelationName(' TbDocuploadinfo@docType '),
            GraphQLSchemaBuilderService::extractRelationColumn(' TbDocuploadinfo@docType '),
        ]);
        $this->assertSame(['policyId', ''], [
            GraphQLSchemaBuilderService::extractRelationName('policyId'),
            GraphQLSchemaBuilderService::extractRelationColumn('policyId'),
        ]);
    }

    public function test_convert_to_underscore_upper_snake_cases_columns(): void
    {
        $this->assertSame('POLICY_NO_ID', GraphQLSchemaBuilderService::convertToUnderscore('policyNoId'));
        $this->assertSame('N_POLICY_NO_ID', GraphQLSchemaBuilderService::convertToUnderscore('n_PolicyNoId'));
        $this->assertSame('POLICY_ID', GraphQLSchemaBuilderService::convertToUnderscore('Policy::Id'));
        $this->assertSame('', GraphQLSchemaBuilderService::convertToUnderscore(''));
    }

    public function test_known_bug_convert_to_underscore_splits_consecutive_capitals(): void
    {
        $this->assertSame('N_POLICY_NO_ID_P_K', GraphQLSchemaBuilderService::convertToUnderscore('n_PolicyNoId_PK'));
        $this->assertSame('POLICY_I_D', GraphQLSchemaBuilderService::convertToUnderscore('policyID'));
    }

    // extractValue() shells out to jq, so it is a required dependency wherever the package runs.
    public function test_jq_executable_is_installed(): void
    {
        exec('jq --version 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, 'The jq executable must be installed: GraphQLSchemaBuilderService::extractValue() depends on it.');
    }

    private function requireJq(): void
    {
        exec('command -v jq >/dev/null 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            $this->markTestSkipped('The jq executable is required for this test.');
        }
    }

    // extractValue() pipes JSON through `echo ... | jq`; Windows escapeshellarg() strips the quotes.
    #[RequiresOperatingSystem('Linux|Darwin')]
    public function test_extract_value_applies_the_jq_filter(): void
    {
        $this->requireJq();
        $data = ['policiesQuery' => ['policyNumber' => 'P-1', 'tags' => ['gold'], 'missing' => null]];

        $this->assertSame('P-1', $this->builder()->extractValue($data, '.policiesQuery.policyNumber'));
        $this->assertSame("[\n  \"gold\"\n]", $this->builder()->extractValue($data, '.policiesQuery.tags'));
        $this->assertSame('null', $this->builder()->extractValue($data, '.policiesQuery.missing'));
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function test_extract_value_returns_false_for_an_invalid_filter(): void
    {
        $this->requireJq();
        $this->assertFalse($this->builder()->extractValue(['a' => 1], '.a |||'));
    }
}
