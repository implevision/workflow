<?php

namespace Taurus\Workflow\Services\WorkflowActions\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class Http
{
    /**
     * Makes an HTTP request.
     *
     * @param  string  $method  The HTTP method to use (e.g., GET, POST, PUT, DELETE).
     * @param  string  $url  The URL to which the request is sent.
     * @param  array  $headers  Optional. An associative array of headers to send with the request.
     * @param  array  $body  Optional. An array representing the body of the request.
     * @return mixed The response from the request, which may vary based on the implementation.
     */
    public static function makeRequest($method, $url, $headers = [], $body = [])
    {
        $client = new Client;

        if (empty($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        $options = ['headers' => $headers];

        if (config('app.env') != 'production') {
            Log::info('WORKFLOW - Request header.', $headers);
            Log::info('WORKFLOW - Request body.', $body);
        }

        $contentType = strtolower($headers['Content-Type']);
        switch ($contentType) {
            case 'application/json':
                $options['json'] = $body;
                break;
            case 'application/x-www-form-urlencoded':
                $options['form_params'] = $body;
                break;
            default:
                throw new \InvalidArgumentException('Unsupported Content-Type: '.$contentType);
        }
        \Log::info('WORKFLOW - Request options.', $options);

        try {
            $response = $client->request($method, $url, $options);

            $responseBody = json_decode($response->getBody(), true);

            $statusCode = $response->getStatusCode();
            Log::info('WORKFLOW - Response status code: '.$statusCode);
            if ($statusCode < 200 || $statusCode >= 300) {
                Log::error('WORKFLOW - Non-2xx response received.', [
                    'status_code' => $statusCode,
                    'url' => $url,
                    'response' => (string) $response->getBody(),
                ]);
            }
            if ($responseBody === null) {
                Log::warning('WORKFLOW - Response body is empty or not valid JSON.');
            } else {
                Log::info('WORKFLOW - Response ', $responseBody);
            }

            return $responseBody;
        } catch (RequestException $e) {
            $errorResponse = $e->getResponse();
            Log::error('WORKFLOW - HTTP request failed.', [
                'status_code' => $errorResponse?->getStatusCode(),
                'url' => $url,
                'message' => $e->getMessage(),
                'response' => $errorResponse ? (string) $errorResponse->getBody() : null,
            ]);

            throw new \Exception('HTTP Request failed: '.$e->getMessage());
        }
    }
}
