<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

class AbstractSchema
{
    protected $headers = [];

    /**
     * Sets the headers for the request.
     *
     * This method allows you to specify an array of headers that will be used
     * in the request. The headers should be provided as key-value pairs.
     *
     * @param  array  $headers  An associative array of headers to set.
     * @return void
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Retrieves the headers for the schema.
     *
     * This method is responsible for returning an array of headers
     * that are necessary for the schema's operation. The headers
     * may include authentication tokens, content types, or any
     * other relevant information required by the schema.
     *
     * @return array An associative array of headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
