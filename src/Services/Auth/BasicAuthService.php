<?php

namespace Taurus\Workflow\Services\Auth;

use Taurus\Workflow\Services\WorkflowActions\Helpers\Http;

/**
 * Class BasicAuthService
 *
 * This class provides basic authentication services by extending the
 * AbstractAuthService. It handles user authentication using basic
 * authentication methods.
 *
 * @extends AbstractAuthService
 */
class BasicAuthService extends AbstractAuthService
{
    /**
     * Authenticates a user based on the provided authentication details.
     *
     * This method verifies the user's credentials and returns an authentication token
     * if the credentials are valid. If the authentication fails, it throws an exception.
     *
     * @param  array  $payload  An associative array containing the user's authentication details,
     *                          such as username and password.
     * @return string Returns an authentication token upon successful authentication.
     *
     * @throws AuthenticationException If the authentication fails due to invalid credentials.
     */
    public function authenticate($payload)
    {
        \Log::info('WORKFLOW - Starting BASIC_AUTH authentication process.');
        $authDetails = $payload['authCredentials'];
        $username = $authDetails['username'];
        $password = $authDetails['password'];
        $authUrl = $payload['authUrl'];
        $authMethod = $payload['authMethod'];
        $authHeaders = $payload['authHeaders'];
        $body = $payload['authParams'];

        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('BASIC_AUTH - Username or password is missing.');
        }

        if (empty($authUrl)) {
            throw new \InvalidArgumentException('BASIC_AUTH - authUrl is missing.');
        }

        $credentials = base64_encode($username.':'.$password);
        $authHeaders['Authorization'] = 'Basic '.$credentials;

        foreach ($authHeaders as $index => $value) {
            if ($value == '{{UUID}}') {
                $authHeaders[$index] = \Str::uuid()->toString();
            }
        }

        try {
            $response = Http::makeRequest($authMethod, $authUrl, $authHeaders, $body);
        } catch (\Exception $e) {
            throw new \Exception('BASIC_AUTH - Authentication request failed: '.$e->getMessage());
        }

        return $response;
    }
}
