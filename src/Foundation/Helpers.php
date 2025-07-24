<?php

use Carbon\Carbon;

/**
 * Get the table prefix for the application.
 *
 * @return string
 */
function getTablePrefix()
{
    return config('workflow.table_prefix', 'tb_taurus');
}

/**
 * Get the current tenant.
 *
 * @return string The tenant name.
 */
function getTenant()
{
    $tenant = config('workflow.single_tenant');

    if (!$tenant) {
        if (function_exists('tenant')) {
            $tenant = tenant('id');
        } else {
            $tenant = getNoTenantIdentifier();
        }
    }

    return $tenant;
}

/**
 * Convert a local datetime to UTC datetime based on the given format and timezone.
 *
 * @param string $datetime The local datetime to convert
 * @param string $format The format of the datetime (default is 'm/d/Y H:i:s')
 * @param string $timezone The timezone of the local datetime (default is 'America/New_York')
 * @return string The UTC datetime
 */
function convertLocalToUTC($datetime, $format = 'm/d/Y H:i:s', $timezone = 'America/New_York')
{
    $localDate = Carbon::createFromFormat($format,  $datetime, $timezone);
    $timeInUTC = $localDate->copy()->setTimezone('UTC');
    return $timeInUTC->format('Y-m-d\TH:i:s');
}


/**
 * Get the no tenant identifier.
 */
function getNoTenantIdentifier()
{
    return "NO_TENANT_FOUND";
}

/**
 * Get the event scheduler group name.
 */
function getEventSchedulerGroupNameToExecuteWorkflow()
{
    return 'workflow-auto-generated-' . getTenant();
}

function getEventSchedulerNameToExecuteWorkflow($identifier)
{
    return 'workflow-id-' . $identifier;
}

function getScheduleGroupTagsToExecuteWorkflow()
{
    return [
        [
            'Key' => 'type',
            'Value' => 'workflow'
        ]
    ];
}

function isTenantBaseSystem()
{
    $tenant = getTenant();
    $noTenantIdentifier = getNoTenantIdentifier();

    if ($tenant == $noTenantIdentifier) {
        return false;
    }
    return true;
}

function getCliCommandToDispatchWorkflow($workflowId, $recordIdentifier = 0)
{
    $command = gitCommandToDispatchWorkflow($workflowId, $recordIdentifier);
    return sprintf("%s %s %s", "php artisan ", $command['command'], implode(", ", $command['options']));
}

function gitCommandToDispatchWorkflow($workflowId, $recordIdentifier = 0)
{
    if (isTenantBaseSystem()) {
        $tenant = getTenant();
        return [
            'command' => 'tenants:run taurus:dispatch-workflow',
            'options' => [
                '--option=workflowId' => $workflowId,
                '--option=recordIdentifier' => $recordIdentifier,
                '--tenants' => $tenant,
            ]
        ];
    } else {
        return [
            'command' => 'taurus:dispatch-workflow',
            'options' => [
                '--workflowId' => $workflowId,
                '--recordIdentifier' => $recordIdentifier
            ]
        ];
    }
}

function getCommandToDispatchMatchingWorkflow($entity, $entityAction, $entityType)
{
    if (isTenantBaseSystem()) {
        $tenant = getTenant();
        return [
            'command' => 'tenants:run taurus:invoke-matching-workflow',
            'options' => [
                '--option=EntityAction' => $entityAction,
                '--option=Entity' => $entity,
                '--option=EntityType' => $entityType,
                '--tenants' => $tenant,
            ]
        ];
    } else {
        return [
            'command' => 'taurus:invoke-matching-workflow',
            'options' => [
                '--EntityAction' => $entityAction,
                '--Entity' => $entity,
                '--EntityType' => $entityType
            ]
        ];
    }
}

function setRunningWorkflowId($workflowId)
{
    app()->instance('workflowId', $workflowId);
}

function getRunningWorkflowId()
{
    return app()->bound('workflowId') ? app('workflowId') : 0;
}

function setRunningJobWorkflowId($jobWorkflowId)
{
    app()->instance('jobWorkflowId', $jobWorkflowId);
}

function getRunningJobWorkflowId()
{
    return app()->bound('jobWorkflowId') ? app('jobWorkflowId') : 0;
}

function setModuleForCurrentWorkflow($module)
{
    app()->instance('moduleForWhichWorkflowRunning', $module);
}

function getModuleForCurrentWorkflow()
{
    return app()->bound('moduleForWhichWorkflowRunning') ? app('moduleForWhichWorkflowRunning') : "";
}

function setRecordIdentifierForRunningWorkflow($recordIdentifier)
{
    app()->instance('recordIdentifier', $recordIdentifier);
}

function getRecordIdentifierForRunningWorkflow()
{
    return app()->bound('recordIdentifier') ? app('recordIdentifier') : 0;
}

function isBound($parameter)
{
    return app()->bound($parameter);
}
