<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

class TbPersonInfoService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'producer';
    }

    /**
     * The base ModuleService::getQueryForRecordIdentifier() resolves $module via app($module)
     * to read its primary key. The "TbPersonInfo" module name is a virtual identifier used only
     * for workflow matching (it maps to Avatar\Infrastructure\Models\Api\v1\TbPersonInfo, which only
     * exists as a Lighthouse GraphQL model in gfs-saas-routeQL, not as a real class in any consuming
     * service), so app($module) always fails to resolve here. gfs-saas-routeQL's own producer GraphQL
     * schema (producersQuery/producerQuery) filters on n_PersonInfoId_PK, so that column is hardcoded
     * instead of resolving it dynamically.
     */
    public function getQueryForRecordIdentifier($module, $recordIdentifier)
    {
        return GraphQLSchemaBuilderService::getQueryMapping('n_PersonInfoId_PK', 'EQ', $recordIdentifier);
    }
}
