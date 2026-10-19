<?php

namespace Taurus\Workflow\Consumer\Common\CreateRecord;

interface CreateRecordHandlerInterface
{
    /**
     * Creates a record from a payload whose placeholders are already resolved.
     *
     * @param  array  $payload  Resolved payload for the record.
     * @param  array  $context  workflowId, jobWorkflowId and recordIdentifier of the running workflow.
     */
    public function execute(array $payload, array $context): mixed;
}
