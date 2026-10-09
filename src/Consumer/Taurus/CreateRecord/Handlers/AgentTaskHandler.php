<?php

namespace Taurus\Workflow\Consumer\Taurus\CreateRecord\Handlers;

use Taurus\Workflow\Consumer\Common\CreateRecord\CreateRecordHandlerInterface;

class AgentTaskHandler implements CreateRecordHandlerInterface
{
    public function execute(array $payload, array $context): mixed
    {
        \Log::info('WORKFLOW - Creating agent task', [
            'workflowId' => $context['workflowId'] ?? null,
            'recordIdentifier' => $context['recordIdentifier'] ?? null,
        ]);

        // TODO: call the Avatar agent task service with the resolved $payload.
        throw new \RuntimeException('Agent task creation is not wired to the Avatar service yet.');
    }
}
