<?php

namespace Taurus\Workflow\Listeners;

use Taurus\Workflow\Events\CustomEvents;

class HandleCustomEvents
{
    public function handle(CustomEvents $event): int
    {
        return true;
    }
}
