<?php

namespace Taurus\Workflow\Services\GraphQL;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Client
{
    protected string $endpoint;

    protected array $headers;

    public function __construct(array $headers = [])
    {
        $this->endpoint = config('workflow.graphql.endpoint');

        $tenant = getTenant();
        $noTenantIdentifier = getNoTenantIdentifier();
        $tenantHeader = ($tenant != $noTenantIdentifier) ? ['X-Tenant' => $tenant] : [];
        $headers = array_merge($tenantHeader, $headers);

        $this->headers = array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);
    }

    public function query(string $query, array $variables = []): array
    {
        $response = Http::withHeaders($this->headers)
            ->post($this->endpoint, [
                'query' => $query,
                'variables' => $variables,
            ]);

        return $this->handleResponse($response);
    }

    public function mutation(string $mutation, array $variables = []): array
    {
        return $this->query($mutation, $variables);
    }

    protected function handleResponse(Response $response): array
    {
        if (! $response->successful()) {
            throw new \Exception('GraphQL request failed: '.$response->body());
        }

        $data = $response->json();

        if (isset($data['errors'])) {
            throw new \Exception('GraphQL errors: '.json_encode($data['errors']));
        }

        return $data['data'] ?? [];
    }

    // Helper method for authenticated requests
    public function withToken(string $token): self
    {
        $this->headers['Authorization'] = "Bearer $token";

        return $this;
    }
}
