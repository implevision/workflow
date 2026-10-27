<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class CeLicenseService extends ModuleService
{
    public function getPostFixForTaskDefinition()
    {
        return 'producer';
    }

    public function isCustomResolverDefinedForModule()
    {
        return true;
    }
}
