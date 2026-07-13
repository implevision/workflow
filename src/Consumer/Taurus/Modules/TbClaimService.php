<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class TbClaimService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'claim';
    }
}
