<?php

namespace Taurus\Workflow\Consumer\Nova\Modules;

use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

class UserService extends ModuleService
{
    /**
     * Override to pass 'adminId' (camelCase) instead of the raw 'Admin_ID' primary key.
     * GraphQLSchemaBuilderService::convertToUnderscore() splits on '_' then re-inserts
     * underscores before each capital, turning 'Admin_ID' → 'ADMIN_I_D' (wrong).
     * Passing 'adminId' converts correctly: 'AdminId' → 'ADMIN_ID'.
     */
    public function getQueryForRecordIdentifier($module, $recordIdentifier): array
    {
        return GraphQLSchemaBuilderService::getQueryMapping('adminID', 'EQ', $recordIdentifier);
    }
}
