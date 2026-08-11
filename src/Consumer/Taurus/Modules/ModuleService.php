<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

use Carbon\Carbon;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

class ModuleService
{
    /**
     * Retrieves matching records for a given effective action.
     *
     * This method queries the database or data source to find records
     * that correspond to the specified effective action criteria.
     *
     * @param  int  $executionFrequency  The execution frequency.
     * @param  string  $executionFrequencyType  The execution frequency type - DAY/MONTH/YEAR.
     * @param  string  $executionEventIncident  The execution event incident - AFTER/BEFORE.
     * @param  string  $executionEvent  The execution event incident - MODULE FIELDS.
     * @return array An array of matching records.
     *
     * @throws \Exception If there is an error during the retrieval process.
     */
    public function getQueryForEffectiveAction(
        $executionFrequency,
        $executionFrequencyType,
        $executionEventIncident,
        $executionEvent
    ) {
        // Without a target date field or a valid window there is nothing to match on.
        if (empty($executionEvent) || empty($executionFrequency) || empty($executionFrequencyType)) {
            return [];
        }

        // If the event is a relation ("relation@column"), split it so the query
        // targets the relation column; otherwise $executionEvent is the column itself.
        $relationName = null;
        if (str_contains($executionEvent, '@')) {
            $relationName = GraphQLSchemaBuilderService::extractRelationName($executionEvent);
            $executionEvent = GraphQLSchemaBuilderService::extractRelationColumn($executionEvent);
        }

        // WITH_IN spans a window (everything from `frequency` units ago up to now)
        // rather than a single day, so it becomes a GTE/LTE range instead of an EQ.
        if (strtoupper((string) $executionEventIncident) === 'WITH_IN') {
            [$from, $to] = $this->resolveEventDateRange($executionFrequency, $executionFrequencyType);

            return [
                'operator' => 'AND',
                'condition' => [
                    GraphQLSchemaBuilderService::getQueryMapping($executionEvent, 'GTE', $from, $relationName),
                    GraphQLSchemaBuilderService::getQueryMapping($executionEvent, 'LTE', $to, $relationName),
                ],
            ];
        }

        $targetDate = $this->resolveEventTargetDate(
            $executionFrequency,
            $executionFrequencyType,
            $executionEventIncident
        );

        // Match records whose event date field equals the target date.
        return GraphQLSchemaBuilderService::getQueryMapping($executionEvent, 'EQ', $targetDate, $relationName);
    }

    /**
     * Resolves the date to match against, relative to today.
     * Reusable by any module that schedules off a "before/after an event" window.
     *
     *   AFTER  -> today + frequency  (event is in the future)
     *   BEFORE -> today - frequency  (event was in the past)
     *
     * @param  int|string  $frequency  Number of units in the window (e.g. 15)
     * @param  string  $frequencyType  DAY | MONTH | YEAR
     * @param  string  $incident  AFTER | BEFORE
     * @return string Target date as 'Y-m-d'
     */
    protected function resolveEventTargetDate($frequency, $frequencyType, $incident): string
    {
        $sign = $incident === 'AFTER' ? '+' : '-';

        return Carbon::parse(sprintf(
            'now %s%d %s',
            $sign,
            (int) $frequency,
            strtolower($frequencyType).'s'
        ))->format('Y-m-d');
    }

    /**
     * Resolves the [from, to] window for a WITH_IN incident: everything from
     * `frequency` units ago up to now. The number of units and the unit both
     * come from the workflow config, so any value is supported.
     *
     * @param  int|string  $frequency  Number of units in the window, from the workflow config
     * @param  string  $frequencyType  DAY | MONTH | YEAR
     * @return array{0: string, 1: string} [from, to] as 'Y-m-d H:i:s'
     */
    protected function resolveEventDateRange($frequency, $frequencyType): array
    {
        $from = Carbon::parse(sprintf(
            'now -%d %s',
            (int) $frequency,
            strtolower($frequencyType).'s'
        ))->startOfDay();

        $to = Carbon::now()->endOfDay();

        return [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')];
    }

    public function getQueryForRecordIdentifier($module, $recordIdentifier)
    {
        $moduleClass = app($module);

        try {
            class_exists($moduleClass::class) or throw new \Exception("Module class $moduleClass does not exist.");
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return [];
        }

        $primaryKey = $moduleClass->getKeyName();

        return GraphQLSchemaBuilderService::getQueryMapping($primaryKey, 'EQ', $recordIdentifier);
    }

    /*private function getGraphQLQueryMapping(
        $module,
        $placeholder,
        $operator,
        $value
    ): string {
        $column = $this->getPlaceHolderMappingForGraphQL($placeholder);

        if (!$column) {
            //to handle the case when the placeholder is not found
            return $this->getQueryForRecordIdentifier($module, -1);
        }
        return GraphQLSchemaBuilderService::getQueryMapping($column, $operator, $value);
    }*/

    public function getServicePostFix($module)
    {
        $module = explode('\\', $module);
        $module = end($module);
        $moduleClass = app("Taurus\\Workflow\\Consumer\\Taurus\\Modules\\$module".'Service');

        try {
            class_exists($moduleClass::class) or throw new \Exception("Module class $moduleClass does not exist.");
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return '';
        }

        return $moduleClass->getPostFixForTaskDefinition();
    }

    public function getPostFixForTaskDefinition()
    {
        return '';
    }

    public function isCustomResolverDefinedForModule()
    {
        return false;
    }

    public function getExtendedTemplateInfo(array $templatePayload = []): array
    {
        return [];
    }
}
