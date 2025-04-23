<?php


namespace Taurus\Workflow\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class WorkflowLogger
{
    protected static $channel = null;

    public static function channel(string $channel): static
    {
        $self = new static;
        $self::$channel = $channel;
        return $self;
    }

    public function __call(string $method, array $args)
    {
        $logger = Log::channel(self::$channel);

        // Reset channel after use
        self::$channel = null;

        $context = $this->getContext($args[1] ?? []);
        return $logger->{$method}($args[0], $context);
    }

    public static function __callStatic(string $method, array $args)
    {
        $logger = Log::channel(config('workflow.log_channel', 'workflow'));

        $context = (new static())->getContext($args[1] ?? []);
        return $logger->{$method}($args[0], $context);
    }

    private function getContext(array $context): array
    {
        return array_merge([
            'user_id' => Auth::id(),
            'ip'      => request()?->ip(),
        ], $context);
    }
}
