<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class TbAgentTasksMasterMappingService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'policy';
    }
}
