<?php

namespace Taurus\Workflow\Consumer;


class ConsumerService
{
    /**
     * Initializes the ConsumerService.
     *
     * This method sets up the necessary configurations and prepares the
     * service for use. It should be called before any other methods
     * of the ConsumerService are invoked.
     *
     * @return void
     */
    public static function init()
    {
        $workflowConsumer = ucwords(strtolower(config('workflow.current_consumer')));
        $consumerServiceClass = "Taurus\\Workflow\\Consumer\\{$workflowConsumer}\\InitInstance";

        if (class_exists($consumerServiceClass)) {
            return new $consumerServiceClass();
        } else {
            throw new \Exception("Consumer service class '$consumerServiceClass' does not exist.");
        }
    }
}
