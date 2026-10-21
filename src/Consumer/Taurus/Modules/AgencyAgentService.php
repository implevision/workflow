<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class AgencyAgentService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'producer';
    }
}

