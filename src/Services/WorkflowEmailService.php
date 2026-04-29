<?php

namespace Taurus\Workflow\Services;

use Illuminate\Support\Facades\Http;

class WorkflowEmailService
{
    public static function getEmailInformation($id)
    {
        $http = Http::withHeaders(['x-client-key' => config('workflow.email_template_service_client_key'), 'X-Tenant' => getTenant()])
            ->acceptJson();

        if (! app()->environment('production')) {
            $http = $http->withoutVerifying();
        }

        $response = $http->get(config('workflow.email_template_service_url').'/api/email/template/get/'.$id);

        if ($response->successful()) {
            $response = $response->json();

            return $response;
        } else {
            throw new \Exception($response->body());
        }
    }
}
