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
 * Get the database connection name for the workflow package.
 *
 * @return string
 */
function getWorkflowDBConnection()
{
    return config('workflow.db_connection');
}

function setWorkflowDBConnection()
{
    $connectionToSet = getWorkflowDBConnection();
    if ($connectionToSet) {
        \Log::info('WORKFLOW - Setting workflow database connection to: '.$connectionToSet);
        $previous = config('database.default');
        config(['database.default' => $connectionToSet]);
        \DB::purge($previous);
        \DB::reconnect($connectionToSet);
    }
}

/**
 * Get the current tenant.
 *
 * @return string The tenant name.
 */
function getTenant()
{
    $tenant = config('workflow.single_tenant');

    if (! $tenant) {
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
 * @param  string  $datetime  The local datetime to convert
 * @param  string  $format  The format of the datetime (default is 'm/d/Y H:i:s')
 * @param  string  $timezone  The timezone of the local datetime (default is 'America/New_York')
 * @return string The UTC datetime
 */
function convertLocalToUTC($datetime, $format = 'm/d/Y H:i:s', $timezone = 'America/New_York')
{
    $localDate = Carbon::createFromFormat($format, $datetime, $timezone);
    $timeInUTC = $localDate->copy()->setTimezone('UTC');

    return $timeInUTC->format('Y-m-d\TH:i:s');
}

/**
 * Get the no tenant identifier.
 */
function getNoTenantIdentifier()
{
    return 'NO_TENANT_FOUND';
}

/**
 * Get the event scheduler group name.
 */
function getEventSchedulerGroupNameToExecuteWorkflow()
{
    return 'workflow-auto-generated-'.getTenant();
}

function getEventSchedulerNameToExecuteWorkflow($identifier)
{
    return 'workflow-id-'.$identifier;
}

function getScheduleGroupTagsToExecuteWorkflow()
{
    return [
        [
            'Key' => 'type',
            'Value' => 'workflow',
        ],
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

    foreach ($command['options'] as $optionKey => $optionValue) {
        if (is_array($optionValue)) {
            if ($optionKey === '--tenants') {
                $command['options'][$optionKey] = implode(',', $optionValue);

                continue;
            }

            if ($optionKey === '--option') {
                foreach ($optionValue as $index => $option) {
                    $command['options'][$optionKey][$index] = sprintf('%s=%s', '--option', $option);
                }

                $command['options'][$optionKey] = implode(' ', $command['options'][$optionKey]);

                continue;
            }
        }
    }

    $parts = [];
    foreach ($command['options'] as $key => $value) {
        if (! str_starts_with($key, '--')) {
            $parts[] = $value;
        } elseif ($key === '--option') {
            $parts[] = $value;
        } else {
            $parts[] = $key.'='.$value;
        }
    }

    return sprintf('php artisan %s %s', $command['command'], implode(' ', $parts));
}

function gitCommandToDispatchWorkflow($workflowId, $recordIdentifier = 0, $data = [], $entityPlaceHoldersToAppend = [], ?string $referenceId = null, $page = 1)
{
    $hasData = ! empty($data);
    $hasPlaceholders = ! empty($entityPlaceHoldersToAppend);
    $hasReferenceId = $referenceId !== null;
    $hasPage = $page > 1;

    $data = json_encode((array) $data);
    $entityPlaceHoldersToAppend = json_encode((array) $entityPlaceHoldersToAppend);

    if (isTenantBaseSystem()) {
        $tenant = getTenant();

        $options = ["workflowId=$workflowId", "recordIdentifier=$recordIdentifier"];
        if ($hasData) {
            $options[] = "data=$data";
        }
        if ($hasPlaceholders) {
            $options[] = "appendPlaceHolders=$entityPlaceHoldersToAppend";
        }
        if ($hasReferenceId) {
            $options[] = "referenceId=$referenceId";
        }
        if ($hasPage) {
            $options[] = "page=$page";
        }

        return [
            'command' => 'tenants:run',
            'options' => [
                'commandname' => 'taurus:dispatch-workflow',
                '--tenants' => [$tenant],
                '--option' => $options,
            ],
        ];
    } else {
        $options = [
            '--workflowId' => $workflowId,
            '--recordIdentifier' => $recordIdentifier,
        ];
        if ($hasData) {
            $options['--data'] = $data;
        }
        if ($hasPlaceholders) {
            $options['--appendPlaceHolders'] = $entityPlaceHoldersToAppend;
        }
        if ($hasReferenceId) {
            $options['--referenceId'] = $referenceId;
        }
        if ($hasPage) {
            $options['--page'] = $page;
        }

        return [
            'command' => 'taurus:dispatch-workflow',
            'options' => $options,
        ];
    }
}

function gitCommandToDispatchManualWorkflow(
    string $module,
    int|string $recordIdentifier = 0,
    array $selectedActions = [],
    array $actionsConfig = []
): array {
    $selectedActions = json_encode($selectedActions);
    $actionsConfig = json_encode($actionsConfig);

    if (isTenantBaseSystem()) {
        $tenant = getTenant();

        return [
            'command' => 'tenants:run',
            'options' => [
                'commandname' => 'taurus:dispatch-manual-workflow',
                '--tenants' => [$tenant],
                '--option' => [
                    "module=$module",
                    "recordIdentifier=$recordIdentifier",
                    "selectedActions=$selectedActions",
                    "actionsConfig=$actionsConfig",
                ],
            ],
        ];
    }

    return [
        'command' => 'taurus:dispatch-manual-workflow',
        'options' => [
            '--module' => $module,
            '--recordIdentifier' => $recordIdentifier,
            '--selectedActions' => $selectedActions,
            '--actionsConfig' => $actionsConfig,
        ],
    ];
}

function getCommandToDispatchMatchingWorkflow($entity, $entityAction, $entityType, $entityData = [], $appendPlaceHolders = [], $updatedFields = [], ?string $referenceId = null)
{
    $entityData = json_encode((array) $entityData);
    $appendPlaceHolders = json_encode((array) $appendPlaceHolders);
    $updatedFields = json_encode((array) $updatedFields);
    if (isTenantBaseSystem()) {
        $tenant = getTenant();

        $options = ["EntityAction=$entityAction", "Entity=$entity", "EntityType=$entityType", "EntityData=$entityData", "EntityPlaceHoldersToAppend=$appendPlaceHolders", "EntityUpdatedFields=$updatedFields"];
        if ($referenceId !== null) {
            $options[] = "EntityReferenceId=$referenceId";
        }

        return [
            'command' => 'tenants:run',
            'options' => [
                'commandname' => 'taurus:invoke-matching-workflow',
                '--tenants' => [$tenant],
                '--option' => $options,
            ],
        ];
    } else {
        $options = [
            '--EntityAction' => $entityAction,
            '--Entity' => $entity,
            '--EntityType' => $entityType,
            '--EntityData' => $entityData,
            '--EntityPlaceHoldersToAppend' => $appendPlaceHolders,
            '--EntityUpdatedFields' => $updatedFields,
        ];
        if ($referenceId !== null) {
            $options['--EntityReferenceId'] = $referenceId;
        }

        return [
            'command' => 'taurus:invoke-matching-workflow',
            'options' => $options,
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
    return app()->bound('moduleForWhichWorkflowRunning') ? app('moduleForWhichWorkflowRunning') : '';
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

function getDefaultQueue()
{
    return config('queue.connections.'.config('queue.default').'.queue');
}
