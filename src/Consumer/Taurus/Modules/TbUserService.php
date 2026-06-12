<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class TbUserService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'auth';
    }
}
