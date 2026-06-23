<?php

namespace Taurus\Workflow\Consumer\Nova\Modules;

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
        // Nova consumer does not build a date/event where-condition yet.
        return [];
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
}
