<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Illuminate\Support\Facades\Cache;
use Taurus\Workflow\Services\Auth\BasicAuthService;

/**
 * Class WebhookAction
 *
 * This class represents a webhook action within the workflow system.
 * It extends the AbstractWorkflowAction to provide specific functionality
 * related to handling webhook events.
 */
class WebhookAction extends AbstractWorkflowAction
{
    /**
     * Handles the webhook action.
     *
     * This method processes the incoming webhook request and performs the necessary actions
     * based on the data received. It may involve validating the request, executing business logic,
     * and returning a response.
     *
     * @return void
     */
    public function handle()
    {
        $this->handleAuthorization();
    }

    /**
     * Handles the authorization process for webhooks.
     *
     * This method is responsible for validating the authorization
     * of incoming webhook requests. It ensures that the request
     * is from a trusted source and meets the necessary security
     * requirements before proceeding with further processing.
     *
     * @return void
     */
    private function handleAuthorization()
    {
        $payload = $this->getPayload();
        $authMethod = $payload['authMethod'];
        if (! $authMethod) {
            return [];
        }

        $accessTokenExpiry = 300;
        $authCredentials = $payload['authCredentials'] ?? [];
        switch ($authMethod) {
            case 'BASIC_AUTH':
                $basicAuthService = new BasicAuthService;
                $authResponse = Cache::remember('BASIC_AUTH_TOKEN', $accessTokenExpiry, function () use ($basicAuthService, $authCredentials) {
                    \Log::info('WORKFLOW - cache hit missed, fetching new BASIC_AUTH token');

                    return $basicAuthService->authenticate($authCredentials);
                });
                $this->updatePayload('authResponse', $authResponse);
                break;
            default:
                \Log::info('WORKFLOW - auth method not available : '.$authMethod);
        }
    }

    /**
     * Retrieves a list of required data for the webhook action.
     *
     * This method is responsible for gathering all necessary data
     * that is required to execute the webhook action successfully.
     *
     * @return array An array containing the required data.
     */
    public function getListOfRequiredData()
    {
        return [];
    }

    /**
     * Retrieves a list of mandate data.
     *
     * This method fetches and returns the mandate data required for processing
     * within the workflow. The data may include various attributes related to
     * mandates, depending on the implementation and requirements.
     *
     * @return array An array containing the mandate data.
     */
    public function getListOfMandateData()
    {
        return [];
    }

    /**
     * Executes the webhook action.
     *
     * This method is responsible for performing the necessary operations
     * when the webhook action is triggered. It may include processing
     * incoming data, interacting with other services, and returning a
     * response.
     *
     * @return void
     */
    public function execute() {}
}
