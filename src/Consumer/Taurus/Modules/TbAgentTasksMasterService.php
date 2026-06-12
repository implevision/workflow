<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class TbAgentTasksMasterService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'policy';
    }
}
