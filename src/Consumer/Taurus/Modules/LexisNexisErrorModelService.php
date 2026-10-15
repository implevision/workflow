<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class LexisNexisErrorModelService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'policy';
    }
}
