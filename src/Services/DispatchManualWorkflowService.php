<?php

namespace Taurus\Workflow\Services;

use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;
use Taurus\Workflow\Services\GraphQL\Client as GraphQLClient;
use Taurus\Workflow\Services\GraphQL\GraphQLSchemaBuilderService;
use Taurus\Workflow\Services\WorkflowActions\EmailAction;

/**
 * Class DispatchManualWorkflowService
 *
 * Executes workflow actions on-the-fly without a pre-configured saved workflow.
 * Unlike DispatchWorkflowService (which loads workflow config from the DB),
 * this service receives action configs directly from the caller (e.g. an API request).
 *
 * Placeholder values are still resolved via GraphQL using the provided
 * module + recordIdentifier, same as the standard workflow engine.
 */
class DispatchManualWorkflowService
{
    protected string $module;

    protected int|string $recordIdentifier;

    protected array $selectedActions;

    protected array $actionsConfig;

    protected JobWorkflowRepository $jobWorkflowRepo;

    protected WorkflowService $workflowService;

    /**
     * @param  string  $module  Module name (e.g. 'policy')
     * @param  int|string  $recordIdentifier  The record ID to resolve placeholders for
     * @param  array  $selectedActions  List of action types to execute (e.g. ['EMAIL'])
     * @param  array  $actionsConfig  Config keyed by action type (e.g. ['EMAIL' => [...]])
     */
    public function __construct(
        string $module,
        int|string $recordIdentifier,
        array $selectedActions,
        array $actionsConfig
    ) {
        $this->module = $module;
        $this->recordIdentifier = $recordIdentifier;
        $this->selectedActions = $selectedActions;
        $this->actionsConfig = $actionsConfig;
        $this->jobWorkflowRepo = app(JobWorkflowRepository::class);
        $this->workflowService = app(WorkflowService::class);
    }

    /**
     * Execute all selected workflow actions.
     */
    public function dispatch(): bool
    {
        if (empty($this->selectedActions)) {
            \Log::error('MANUAL WORKFLOW - No actions selected.');

            return false;
        }

        $jobWorkflowId = $this->createJobWorkflow();
        if (! $jobWorkflowId) {
            return false;
        }

        setModuleForCurrentWorkflow($this->module);
        setRunningJobWorkflowId($jobWorkflowId);

        \Log::info('MANUAL WORKFLOW - Starting execution', [
            'module' => $this->module,
            'recordIdentifier' => $this->recordIdentifier,
            'selectedActions' => $this->selectedActions,
        ]);

        foreach ($this->selectedActions as $actionType) {
            $actionPayload = $this->actionsConfig[$actionType] ?? null;

            if (! $actionPayload) {
                \Log::error("MANUAL WORKFLOW - No config found for action: {$actionType}");

                continue;
            }

            // Instantiate and initialise the action class
            $actionToExecute = null;
            switch ($actionType) {
                case 'EMAIL':
                    try {
                        $actionToExecute = new EmailAction($actionType, $actionPayload);
                        $actionToExecute->handle();
                    } catch (\Exception $e) {
                        \Log::error('MANUAL WORKFLOW - Error initiating email action: '.$e->getMessage());

                        continue 2;
                    }
                    break;

                default:
                    \Log::error("MANUAL WORKFLOW - Unsupported action type: {$actionType}");

                    continue 2;
            }

            // Determine placeholders required by the action
            try {
                $listOfRequiredData = $actionToExecute->getListOfRequiredData();
                $listOfMandateData = $actionToExecute->getListOfMandateData();

                // For email actions, the recipient field must also be fetched unless it is CUSTOM
                if ($actionType == 'EMAIL' && strtoupper($actionPayload['emailRecipient']) != 'CUSTOM') {
                    $listOfRequiredData[] = $listOfMandateData[] = ucfirst($actionPayload['emailRecipient']);
                }
            } catch (\Exception $e) {
                \Log::error('MANUAL WORKFLOW - Error getting required data for '.$actionType.': '.$e->getMessage());

                continue;
            }

            // Fetch placeholder values from GraphQL using the record identifier
            $data = [];
            try {
                $moduleClassForGraphQL = $this->workflowService->getGraphQLQueryMappingService($this->module);
                $fieldMapping = $moduleClassForGraphQL->getFieldMapping();
                $queryName = $moduleClassForGraphQL->getQueryName();

                $graphQLQuery = $this->workflowService->getQueryForRecordIdentifier(
                    $this->module,
                    $this->recordIdentifier
                );

                $graphQLSchemaBuilder = new GraphQLSchemaBuilderService($fieldMapping);
                foreach ($listOfRequiredData as $placeHolder) {
                    $graphQLSchemaBuilder->addField($placeHolder);
                }

                $schemaData = $graphQLSchemaBuilder->getSchema();
                $graphQLRequestPayload = $graphQLSchemaBuilder->generateGraphQLQuery(
                    $schemaData,
                    $queryName,
                    $graphQLQuery
                );

                $graphQLClient = new GraphQLClient;
                $response = $graphQLClient->query($graphQLRequestPayload);
            } catch (\Exception $e) {
                \Log::error('MANUAL WORKFLOW - Error executing GraphQL query: '.$e->getMessage());

                continue;
            }

            // Parse placeholder values from GraphQL response
            try {
                $parsedData = [];
                foreach ($listOfRequiredData as $placeHolder) {
                    if (! array_key_exists($placeHolder, $fieldMapping)) {
                        \Log::error('MANUAL WORKFLOW - Field mapping not found for placeholder: '.$placeHolder);
                        $parsedData[$placeHolder] = '';

                        continue;
                    }

                    $jqFilter = $fieldMapping[$placeHolder]['jqFilter'];
                    $parseResultCallback = ! empty($fieldMapping[$placeHolder]['parseResultCallback'])
                        ? $fieldMapping[$placeHolder]['parseResultCallback']
                        : null;

                    $placeHolderValue = '';
                    if (! $jqFilter && $parseResultCallback) {
                        if (method_exists($moduleClassForGraphQL, $parseResultCallback)) {
                            $placeHolderValue = $moduleClassForGraphQL->$parseResultCallback();
                        }
                    } else {
                        $placeHolderValue = $graphQLSchemaBuilder->extractValue($response, $jqFilter);

                        if ($placeHolderValue) {
                            $parsed = json_decode($placeHolderValue, true);
                            $placeHolderValue = json_last_error() === JSON_ERROR_NONE ? $parsed : $placeHolderValue;

                            if ($parseResultCallback) {
                                if (method_exists($moduleClassForGraphQL, $parseResultCallback)) {
                                    $placeHolderValue = $moduleClassForGraphQL->$parseResultCallback($placeHolderValue);
                                }
                            }
                        }
                    }

                    $parsedData[$placeHolder] = $placeHolderValue;
                }

                $data[] = $parsedData;
            } catch (\Exception $e) {
                \Log::error('MANUAL WORKFLOW - Error parsing GraphQL response: '.$e->getMessage());

                continue;
            }

            if (config('app.env') != 'production') {
                \Log::info('MANUAL WORKFLOW - Resolved data: ', $data);
            }

            // Validate mandate data and resolve email address, then execute
            try {
                $hasPriorDataForWorkflow = false;

                foreach ($data as $index => $dataItem) {
                    $data[$index]['hasPriorDataForWorkflow'] = true;
                    foreach ($listOfMandateData as $mandateData) {
                        if (! isset($dataItem[$mandateData]) || empty($dataItem[$mandateData])) {
                            $data[$index]['hasPriorDataForWorkflow'] = false;
                            break;
                        }
                    }

                    if ($data[$index]['hasPriorDataForWorkflow']) {
                        $hasPriorDataForWorkflow = true;
                    } else {
                        \Log::warning('MANUAL WORKFLOW - Missing mandate data', [
                            'data' => $data[$index],
                            'listOfMandateData' => $listOfMandateData,
                        ]);
                        unset($data[$index]);

                        continue;
                    }

                    if ($actionType == 'EMAIL') {
                        if (! empty($actionPayload['emailRecipient']) && strtoupper($actionPayload['emailRecipient']) == 'CUSTOM') {
                            $emailPlaceHolderValue = $actionPayload['customEmailRecipients'];
                        } else {
                            $emailPlaceHolder = ucfirst($actionPayload['emailRecipient']);
                            $emailPlaceHolderValue = ! empty($data[$index][$emailPlaceHolder]) ? $data[$index][$emailPlaceHolder] : '';
                        }

                        \Log::info('MANUAL WORKFLOW - Actual email address: '.$emailPlaceHolderValue);

                        if (config('app.env') != 'production') {
                            $sendAllEmailsTo = config('workflow.send_all_workflow_email_to');

                            if ($sendAllEmailsTo) {
                                $emailPlaceHolderValue = $sendAllEmailsTo;
                            }

                            $emailPlaceHolderValue = explode(',', $emailPlaceHolderValue);

                            $executeEmailAction = false;
                            $allowedEmailAddressList1 = array_intersect($emailPlaceHolderValue, config('workflow.allowed_receiver.email'));
                            if (count($allowedEmailAddressList1) > 0) {
                                $executeEmailAction = true;
                            }

                            $allowedEmailAddressList2 = [];
                            $allowedEmailShouldEndsWithInNonProduction = array_merge(['@thinktaurus.com'], config('workflow.allowed_receiver.ends_with'));
                            foreach ($allowedEmailShouldEndsWithInNonProduction as $endsWith) {
                                foreach ((array) $emailPlaceHolderValue as $singleEmail) {
                                    if (str_ends_with($singleEmail, $endsWith)) {
                                        $executeEmailAction = true;
                                        $allowedEmailAddressList2[] = $singleEmail;
                                    }
                                }
                            }

                            $finalList = [...$allowedEmailAddressList1, ...$allowedEmailAddressList2];

                            if ($executeEmailAction && count($finalList) > 0) {
                                $data[$index]['email'] = $emailPlaceHolderValue;
                            } else {
                                \Log::error('MANUAL WORKFLOW - Email address not allowed in non-production env: '.$emailPlaceHolderValue);
                                $hasPriorDataForWorkflow = false;
                                unset($data[$index]);

                                continue;
                            }
                        } else {
                            $data[$index]['email'] = explode(',', $emailPlaceHolderValue);
                        }
                    }
                }

                if ($hasPriorDataForWorkflow === false && count($data) == 0) {
                    continue;
                }

                $actionToExecute->setWorkflowData(0, $jobWorkflowId, $this->recordIdentifier);
                $actionToExecute->setDataForAction('', $data);
                $actionToExecute->execute();
            } catch (\Exception $e) {
                \Log::error('MANUAL WORKFLOW - Error while executing action '.$actionType.': '.$e->getMessage());

                continue;
            }
        }

        return true;
    }

    /**
     * Create a JobWorkflow entry to track this manual execution.
     * Uses workflow_id = null since there is no saved workflow record.
     */
    private function createJobWorkflow(): int
    {
        try {
            $jobWorkflowId = $this->jobWorkflowRepo->createSingle([
                'workflow_id' => null,
                'status' => 'CREATED',
                'total_no_of_records_to_execute' => 0,
                'total_no_of_records_executed' => 0,
                'response' => [],
            ]);

            setRunningWorkflowId(null);

            return $jobWorkflowId;
        } catch (\Exception $e) {
            \Log::error('MANUAL WORKFLOW - Error creating JobWorkflow entry: '.$e->getMessage());

            return 0;
        }
    }
}
