<?php


namespace Taurus\Workflow\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


class WorkflowLogger
{
    protected static ?string $channel = null;

    public static function channel(string $channel): static
    {
        self::$channel = $channel;
        return new static;
    }

    public function __call(string $method, array $args)
    {
        return $this->log($method, $args);
    }

    public static function __callStatic(string $method, array $args)
    {
        return (new static)->log($method, $args);
    }

    protected function log(string $method, array $args)
    {
        $logger = Log::channel(
            self::$channel ?? config('workflow.log_channel', 'workflow')
        );

        self::$channel = null;

        $payload = json_encode(array_merge([
            'user_id' => Auth::id() ?? 0,
            'ip' => request()?->ip() ?? '',
            'message' => $args[0] ?? '',
        ], $args[1] ?? []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $logger->{$method}($payload);
    }
}
