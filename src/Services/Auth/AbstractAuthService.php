<?php

namespace Taurus\Workflow\Services\Auth;

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
}
