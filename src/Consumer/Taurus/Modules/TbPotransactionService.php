<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class TbPotransactionService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'policy';
    }
}
