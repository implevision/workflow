<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class TbPersonInfoService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'producer';
    }
}
