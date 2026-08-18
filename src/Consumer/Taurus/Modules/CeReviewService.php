<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

class CeReviewService extends ModuleService
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
