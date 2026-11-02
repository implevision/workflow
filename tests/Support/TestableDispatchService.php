<?php

namespace Taurus\Workflow\Tests\Support;

use Taurus\Workflow\Services\AbstractDispatchService;

class TestableDispatchService extends AbstractDispatchService
{
    public function __construct()
    {
        $this->module = WorkflowFactory::MODULE;
        $this->initializeServices();
    }

    public function dispatch()
    {
        return null;
    }

    public function call(string $method, mixed ...$arguments): mixed
    {
        return $this->{$method}(...$arguments);
    }
}
