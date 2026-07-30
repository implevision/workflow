<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class DeclarationPageService extends ModuleService
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
