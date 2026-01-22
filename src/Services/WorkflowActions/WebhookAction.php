<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Illuminate\Support\Facades\Cache;
use Taurus\Workflow\Services\Auth\BasicAuthService;
use Taurus\Workflow\Services\WorkflowActions\Helpers\Http;

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
    public function handle() {}

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
        $authType = $payload['authType'];
        if (! $authType) {
            return [];
        }

        $accessTokenExpiryTimeInSeconds = $payload['accessTokenExpiryTimeInSeconds'];
        switch ($authType) {
            case 'BASIC_AUTH':
                $basicAuthService = new BasicAuthService;
                $authUrl = $payload['authUrl'];
                // Create unique cache key per tenant and baseUrl, in case multiple webhooks are used
                $cacheKey = 'BASIC_AUTH_TOKEN_'.md5($authUrl);
                $authResponse = Cache::remember($cacheKey, $accessTokenExpiryTimeInSeconds, function () use ($payload, $basicAuthService) {
                    \Log::info('WORKFLOW - cache hit missed, fetching new BASIC_AUTH token');

                    return $basicAuthService->authenticate($payload);
                });
                if (config('app.env') != 'production') {
                    \Log::info('WORKFLOW - Auth Response', $authResponse);
                }
                $this->updatePayload('authResponse', $authResponse);
                break;
            default:
                \Log::info('WORKFLOW - auth type not available : '.$authType);
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
        // TODO: Need to come from DB. HARDCODED for farmers release
        return [
            'Type',
            'DueDate',
            'SubType',
            'ReasonCode',
            'CreatedAt',
            'Task',
            'DocumentName',
            'PolicyNumber',
            'SourceSystem',
            'WyoAgencyAgentCode',
            'PremiumDue',
            'PremiumCapDiscountAmount',
        ];
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
        // TODO: Need to come from DB. HARDCODED for farmers release
        return [
            'Type',
            'SubType',
        ];
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
    public function execute()
    {

        $workflowId = $this->getWorkflowId();
        $jobWorkflowId = $this->getJobWorkflowId();
        $recordIdentifier = $this->getRecordIdentifier();
        $feedFile = $this->getFeedFile();
        $data = $this->getData();
        $payload = $this->getPayload();

        $webhookRequestMethod = $payload['webhookRequestMethod'];
        $webhookRequestUrl = $payload['webhookRequestUrl'];
        $webhookRequestHeaders = $payload['webhookRequestHeaders'];
        $webhookRequestPayload = $payload['webhookRequestPayload'];

        if ($feedFile) {
            // TOOD: Process feed file if required
            return false;
        }

        $webhookRequestHeaders = $this->replacePlaceholders($webhookRequestHeaders, ['UUID' => \Str::uuid()->toString()]);
        $this->handleAuthorization();
        $webhookRequestHeaders = $this->updateHeadersWithAuthResponse($webhookRequestHeaders);

        if ($data) {
            preg_match_all('/{{\s*(.*?)\s*}}/', $webhookRequestUrl, $webhookRequestUrlPlaceholderMatches);
            foreach ($data as $placeHolderData) {
                $requestUrl = $this->replacePlaceholders($webhookRequestUrl, $placeHolderData, true);
                $requestPayload = $this->replacePlaceholders($webhookRequestPayload, $placeHolderData, true);
                try {
                    Http::makeRequest($webhookRequestMethod, $requestUrl, $webhookRequestHeaders, $requestPayload);
                } catch (\Exception $e) {
                    throw new \Exception('Webhook execution failed: '.$e->getMessage());
                }
            }
        }
    }

    private function replacePlaceholders($input, $placeholders, $replaceWithEmptySpaceIfNotAvailable = false)
    {
        if (is_array($input)) {
            return array_map(function ($item) use ($placeholders, $replaceWithEmptySpaceIfNotAvailable) {
                return $this->replacePlaceholders($item, $placeholders, $replaceWithEmptySpaceIfNotAvailable);
            }, $input);
        }

        return preg_replace_callback('/{{\s*(.*?)\s*}}/', function ($matches) use ($placeholders, $replaceWithEmptySpaceIfNotAvailable) {
            $placeholder = $matches[1];

            $defaultValue = $replaceWithEmptySpaceIfNotAvailable ? '' : '{{'.$placeholder.'}}';

            return array_key_exists($placeholder, $placeholders) ? $placeholders[$placeholder] : $defaultValue;
        }, $input);
    }

    private function updateHeadersWithAuthResponse($webhookRequestHeaders)
    {
        // GET UPDATED PAYLOAD WITH AUTH RESPONSE
        $payload = $this->getPayload();

        // TODO: add support for multilevel auth token extraction.
        return $this->replacePlaceholders($webhookRequestHeaders, $payload['authResponse'] ?? []);
    }
}
