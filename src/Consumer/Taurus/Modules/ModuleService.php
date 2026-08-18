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

        $targetDate = $this->resolveEventTargetDate(
            $executionFrequency,
            $executionFrequencyType,
            $executionEventIncident
        );

        // If the execution event is a relation, extract the relation name and column.
        $relationName = null;
        $column = $executionEvent;

        if (str_contains($executionEvent, '@')) {
            $relationName = GraphQLSchemaBuilderService::extractRelationName($executionEvent);
            $column = GraphQLSchemaBuilderService::extractRelationColumn($executionEvent);
        }

        // WITH_IN resolves to a range, so match records whose event date field falls between START_DATE and END_DATE.
        if (is_array($targetDate)) {
            $startCondition = GraphQLSchemaBuilderService::getQueryMapping($column, 'GTE', $targetDate['START_DATE'], $relationName);
            $endCondition = GraphQLSchemaBuilderService::getQueryMapping($column, 'LTE', $targetDate['END_DATE'], $relationName);

            return $startCondition + ['JOIN' => ['operator' => 'AND', 'condition' => [$endCondition]]];
        }

        // Match records whose event date field (within the relation, if any) equals the target date.
        return GraphQLSchemaBuilderService::getQueryMapping($column, 'EQ', $targetDate, $relationName);
    }

    /**
     * Resolves the date(s) to match against, relative to today.
     * Reusable by any module that schedules off a "before/after/within an event" window.
     *
     *   AFTER   -> today - frequency          (event is in the future)
     *   BEFORE  -> today + frequency          (event was in the past)
     *   WITH_IN -> START_DATE = today - frequency, END_DATE = today (event falls within the trailing window)
     *
     * @param  int|string  $frequency  Number of units in the window (e.g. 15)
     * @param  string  $frequencyType  DAY | MONTH | YEAR
     * @param  string  $incident  AFTER | BEFORE | WITH_IN
     * @return string|array Target date as 'Y-m-d', or ['START_DATE' => ..., 'END_DATE' => ...] for WITH_IN
     */
    protected function resolveEventTargetDate($frequency, $frequencyType, $incident): string|array
    {
        $unit = strtolower($frequencyType).'s';

        if ($incident === 'WITH_IN') {
            return [
                'START_DATE' => Carbon::parse(sprintf('now -%d %s', (int) $frequency, $unit))->format('Y-m-d'),
                'END_DATE' => Carbon::now()->format('Y-m-d'),
            ];
        }

        $sign = $incident === 'AFTER' ? '-' : '+';

        return Carbon::parse(sprintf(
            'now %s%d %s',
            $sign,
            (int) $frequency,
            $unit
        ))->format('Y-m-d');
    }

    /**
     * Resolves the [from, to] window for a WITH_IN incident: everything from
     * now up to `frequency` units ahead. The number of units and the unit both
     * come from the workflow config, so any value is supported.
     *
     * @param  int|string  $frequency  Number of units in the window, from the workflow config
     * @param  string  $frequencyType  DAY | MONTH | YEAR
     * @return array{0: string, 1: string} [from, to] as 'Y-m-d H:i:s'
     */
    protected function resolveEventDateRange($frequency, $frequencyType): array
    {
        $from = Carbon::now()->startOfDay();

        $to = Carbon::parse(sprintf(
            'now +%d %s',
            (int) $frequency,
            strtolower($frequencyType).'s'
        ))->endOfDay();

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
