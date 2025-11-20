<?php

namespace Taurus\Workflow\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class AbstractAuthService
 *
 * This is an abstract class that provides the foundation for authentication services.
 * It may contain common properties and methods that can be utilized by concrete
 * authentication service implementations.
 */
class AbstractAuthService
{
    /**
     * Authenticates a user based on the provided authentication details.
     *
     * This method takes an array of authentication details and attempts to
     * verify the user's identity. It returns a boolean indicating whether
     * the authentication was successful or not.
     *
     * @param  array  $authDetails  An associative array containing authentication details such as username and password.
     * @return array Returns authentication response.
     */
    public function authenticate($authDetails)
    {
        // This method should be implemented by subclasses
        throw new \BadMethodCallException('Method authenticate() must be implemented in subclass.');
    }

    /**
     * Makes an HTTP request.
     *
     * @param  string  $method  The HTTP method to use (e.g., GET, POST, PUT, DELETE).
     * @param  string  $url  The URL to which the request is sent.
     * @param  array  $headers  Optional. An associative array of headers to send with the request.
     * @param  array  $body  Optional. An array representing the body of the request.
     * @return mixed The response from the request, which may vary based on the implementation.
     */
    public function makeRequest($method, $url, $headers = [], $body = [])
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
