<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class PolicyRenewalService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'policy';
    }

    public function isCustomResolverDefinedForModule()
    {
        return true;
    }
}
