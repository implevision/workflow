<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

class TbUserService extends ModuleService
{
    /**
     * The user GraphQL query uses direct arguments (e.g. user(Admin_ID: "205"))
     * instead of the standard where-clause format used by list queries.
     */
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

        return ['DIRECT_ARGS' => [$primaryKey => $recordIdentifier]];
    }
}
