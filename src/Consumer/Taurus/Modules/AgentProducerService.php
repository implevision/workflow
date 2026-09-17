<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;

class AgentProducerService extends ModuleService
{
    public function getQueryForRecordIdentifier($module, $recordIdentifier)
    {
        $moduleClass = new \Avatar\Infrastructure\Models\Api\v1\TbPersonInfo;
        $primaryKey = $moduleClass->getKeyName();

        return GraphQLSchemaBuilderService::getQueryMapping($primaryKey, 'EQ', $recordIdentifier);
    }
}
