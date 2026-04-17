<?php

namespace Taurus\Workflow\Console\Commands\Seeders\Contracts;

interface SeederValueResolverInterface
{
    /**
     * Resolve a symbolic argument into a tenant-specific concrete value.
     *
     * @param  string  $argument  The argument extracted from the placeholder, e.g. "NFIP" from "{{product_by_code@NFIP}}"
     * @return mixed The resolved value to substitute into expectedValue
     */
    public function resolve(string $argument): mixed;
}
