<?php

namespace Taurus\Workflow\Events;

trait DispatchCustomEvents
{
    public static function fireEvent($event, $eventVirtualModel, $data = [], $recordIdentifier = null, $appendPlaceHolders = [], $halt = false)
    {
        $data = array_merge($data, ['recordIdentifier' => $recordIdentifier]);
        ! $halt ?
            event(new AsyncCustomEvents($event, $eventVirtualModel, $data, $appendPlaceHolders)) :
            event(new CustomEvents($event, $eventVirtualModel, $data, $appendPlaceHolders));

        return true;
    }
}
