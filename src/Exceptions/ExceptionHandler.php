<?php

namespace Taurus\Workflow\Exceptions;

use Throwable;

class ExceptionHandler
{
    public function handle(Throwable $e): void
    {
        $context = [
            'exception' => $e,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->take(5)->toArray(),
        ];

        WorkflowLogger::error('Exception caught: ' . $e->getMessage(), $context);

        throw $e;
    }
}
