<?php

namespace Taurus\Workflow\Tests\Consumer\Taurus;

use Taurus\Workflow\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('workflow.current_consumer', 'taurus');
    }
}
