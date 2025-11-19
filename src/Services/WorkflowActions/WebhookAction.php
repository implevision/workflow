<?php

namespace Taurus\Workflow\Services\WorkflowActions;

use Illuminate\Support\Facades\Cache;
use Taurus\Workflow\Services\Auth\BasicAuthService;

class WebhookAction extends AbstractWorkflowAction
{
    public function handle()
    {
        $this->handleAuthorization();
    }

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

    public function getListOfRequiredData()
    {
        return [];
    }

    public function getListOfMandateData()
    {
        return [];
    }

    public function execute() {}
}
