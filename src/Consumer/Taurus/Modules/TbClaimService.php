<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

use Carbon\Carbon;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

class TbClaimService
{
    /**
     * Retrieves matching records for a given effective action.
     *
     * This method queries the database or data source to find records
     * that correspond to the specified effective action criteria.
     *
     * @param int $executionFrequency The execution frequency.
     * @param string $executionFrequencyType The execution frequency type - DAY/MONTH/YEAR.
     * @param string $executionEventIncident The execution event incident - AFTER/BEFORE.
     * @param string $executionEvent The execution event incident - MODULE FIELDS.
     * 
     * @return array An array of matching records.
     * @throws \Exception If there is an error during the retrieval process.
     */
    public function getQueryForEffectiveAction(
        $module,
        $executionFrequency,
        $executionFrequencyType,
        $executionEventIncident,
        $executionEvent
    ) {
        // 'Now {+/-}{NO_OF_DAYS} {days/months/years}' format
        $timeStrToParse = sprintf(
            'Now %s%s %s',
            $executionEventIncident == 'AFTER' ? '+' : '-',
            $executionFrequency,
            strtolower($executionFrequencyType) . 's',
        );
        $timeToParse = Carbon::parse($timeStrToParse);

        //$data = $this->getGraphQLQueryMapping($module, $executionEvent, "=", $timeToParse->format('Y-m-d'));

        return $data;
    }


    public function getQueryForRecordIdentifier($module, $recordIdentifier)
    {
        \Log::info($module);
        $moduleClass = app($module);

        try {
            class_exists($moduleClass::class) or throw new \Exception("Module class $moduleClass does not exist.");
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return [];
        }

        $primaryKey = $moduleClass->getKeyName();

        return GraphQLSchemaBuilderService::getQueryMapping($primaryKey, "EQ", $recordIdentifier);
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
