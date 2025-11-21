<?php

namespace Taurus\Workflow\Services\WorkflowActions\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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

        $contentType = strtolower($headers['Content-Type']);
        switch ($contentType) {
            case 'application/json':
                $options['json'] = json_encode($body);
                break;
            case 'application/x-www-form-urlencoded':
                $options['form_params'] = $body;
                break;
            default:
                throw new \InvalidArgumentException('Unsupported Content-Type: ' . $contentType);
        }

        try {
            $response = $client->request($method, $url, $options);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \Exception('HTTP Request failed: ' . $e->getMessage());
        }
    }
}
