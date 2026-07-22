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

        // Resolve placeholders (e.g. {{api_key}}, {{api_secret}}, {{X-Client-key}}) in
        // authHeaders and authParams before the auth request is made.
        $data = $this->getData();
        if (! empty($data)) {
            $firstRecord = reset($data);
            $this->updatePayload('authHeaders', $this->replacePlaceholders($payload['authHeaders'] ?? [], $firstRecord, true));
            $this->updatePayload('authParams', $this->replacePlaceholders($payload['authParams'] ?? [], $firstRecord, true));
            $payload = $this->getPayload();
        }

        $accessTokenExpiryTimeInSeconds = $payload['accessTokenExpiryTimeInSeconds'];
        switch ($authType) {
            case 'BASIC_AUTH':
                $basicAuthService = new BasicAuthService;
                $authUrl = $payload['authUrl'];
                // Cache key includes a hash of resolved auth params so different clients
                // with the same authUrl get separate cache entries.
                $cacheKey = 'BASIC_AUTH_TOKEN_'.md5($authUrl.json_encode($payload['authParams'] ?? []).json_encode($payload['authCredentials'] ?? []));
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
        $payload = $this->getPayload();
        $extractedPlaceHolder = ! empty($payload['extractedPlaceholders']) ? $payload['extractedPlaceholders'] : [];

        if (! empty($extractedPlaceHolder)) {
            return $extractedPlaceHolder;
        }

        $searchIn = [
            $payload['webhookRequestPayload'] ?? [],
            $payload['webhookRequestHeaders'] ?? [],
            $payload['webhookRequestUrl'] ?? '',
            $payload['authHeaders'] ?? [],
            $payload['authParams'] ?? [],
        ];

        $placeholders = [];
        array_walk_recursive($searchIn, function ($value) use (&$placeholders) {
            preg_match_all('/{{\s*(.*?)\s*}}/', $value ?? '', $matches);
            $placeholders = array_merge($placeholders, $matches[1]);
        });

        return array_unique($placeholders);
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
        $payload = $this->getPayload();

        return ! empty($payload['mandatoryPlaceholders']) ? $payload['mandatoryPlaceholders'] : [];
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
            foreach ($data as $placeHolderData) {
                $requestHeaders = $this->replacePlaceholders($webhookRequestHeaders, $placeHolderData, true);
                $requestUrl = $this->replacePlaceholders($webhookRequestUrl, $placeHolderData, true);
                $requestPayload = $this->replacePlaceholders($webhookRequestPayload, $placeHolderData, true);
                try {
                    Http::makeRequest($webhookRequestMethod, $requestUrl, $requestHeaders, $requestPayload);
                } catch (\Exception $e) {
                    throw new \Exception('Webhook execution failed: '.$e->getMessage());
                }
            }
        }
    }

    private function replacePlaceholders($input, $placeholders, $replaceWithEmptySpaceIfNotAvailable = false, $resolveNested = false)
    {
        if (is_array($input)) {
            return array_map(function ($item) use ($placeholders, $replaceWithEmptySpaceIfNotAvailable, $resolveNested) {
                return $this->replacePlaceholders($item, $placeholders, $replaceWithEmptySpaceIfNotAvailable, $resolveNested);
            }, $input);
        }

        return preg_replace_callback('/{{\s*(.*?)\s*}}|\[\[\s*(.*?)\s*\]\]/', function ($matches) use ($placeholders, $replaceWithEmptySpaceIfNotAvailable, $resolveNested) {
            $isBracketStyle = ! isset($matches[1]) || $matches[1] === '';
            $placeholder = $isBracketStyle ? ($matches[2] ?? '') : $matches[1];
            $defaultValue = $replaceWithEmptySpaceIfNotAvailable ? '' : ($isBracketStyle ? '[['.$placeholder.']]' : '{{'.$placeholder.'}}');

            if ($resolveNested && is_array($placeholders)) {
                $value = $this->resolveNestedValue($placeholders, $placeholder);

                return $value !== null ? $value : $defaultValue;
            }

            return isset($placeholders[$placeholder]) ? $placeholders[$placeholder] : $defaultValue;
        }, $input);
    }

    private function resolveNestedValue(array $data, string $key): mixed
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        $keys = explode('.', $key);
        $value = $data;
        foreach ($keys as $k) {
            if (! is_array($value) || ! array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }

    private function updateHeadersWithAuthResponse($webhookRequestHeaders)
    {
        $payload = $this->getPayload();
        $authResponse = $payload['authResponse'] ?? [];

        return $this->replacePlaceholders($webhookRequestHeaders, $authResponse, false, true);
    }
}
