<?php

namespace Taurus\Workflow\Events;

trait DispatchCustomEvents
{
    /**
     * User exposed observable events.
     *
     * These are extra user-defined events observers may subscribe to.
     *
     * @var array
     */
    protected $observables = [];

    /**
     * Get the observable event names.
     *
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            [
                'created',
                'updated',
                'deleted',
            ],
            $this->observables
        );
    }

    /**
     * Set the observable event names.
     *
     * @return $this
     */
    public function setObservableEvents(array $observables)
    {
        $this->observables = $observables;

        return $this;
    }

    protected function fireEvent($event, $eventVirtualModel, $data = [], $recordIdentifier = null, $halt = true)
    {
        $data = array_merge($data, ['recordIdentifier' => $recordIdentifier]);
        if (in_array($event, $this->getObservableEvents())) {
            $halt ?
                event(new AsyncCustomEvents($event, $eventVirtualModel, $data)) :
                event(new CustomEvents($event, $eventVirtualModel, $data));
        }

        return true;
    }
}
