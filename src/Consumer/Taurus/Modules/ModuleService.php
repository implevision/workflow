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

        // Match records whose event date field equals the target date.
        return GraphQLSchemaBuilderService::getQueryMapping($executionEvent, 'EQ', $targetDate);
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
}
