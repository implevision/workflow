<?php

namespace Taurus\Workflow\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AbstractAuthService
{
    public function authenticate($authDetails)
    {
        // This method should be implemented by subclasses
        throw new \BadMethodCallException('Method authenticate() must be implemented in subclass.');
    }

    public function makeRequest($method, $url, $headers = [], $body = [])
    {
        $client = new Client;

        if (empty($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        $contentType = strtolower($headers['Content-Type']);

        $options = ['headers' => $headers];
        switch ($contentType) {
            case 'application/json':
                $options['json'] = json_encode($body);
                break;
            case 'application/x-www-form-urlencoded':
                $options['form_params'] = $body;
                break;
            default:
                throw new \InvalidArgumentException('Unsupported Content-Type: '.$contentType);
        }

        try {
            $response = $client->request($method, $url, $options);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \Exception('HTTP Request failed: '.$e->getMessage());
        }
    }
}
