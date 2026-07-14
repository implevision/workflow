<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class TbQuotepolicyService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'agent-portal';
    }
}
