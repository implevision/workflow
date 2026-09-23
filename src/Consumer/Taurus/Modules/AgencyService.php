<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class AgencyService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'producer';
    }
}
