<?php

namespace Taurus\Workflow\Consumer\Fake;

// Resolved by ConsumerService::init() when workflow.current_consumer is 'fake'; a Throwable service is thrown instead of returned.
class InitInstance
{
    public static array $services = [];

    public static array $calls = [];

    public function getModuleService($module)
    {
        return self::resolve('module', $module);
    }

    public function getGraphQLQueryMappingService($module, $appendPlaceHolders = [])
    {
        return self::resolve('graphQL', $module, $appendPlaceHolders);
    }

    public function getPostActionService()
    {
        return self::resolve('postAction');
    }

    public function getCreateRecordService()
    {
        return self::resolve('createRecord');
    }

    public function getParentClassService()
    {
        return self::resolve('parentClass');
    }

    public static function reset(): void
    {
        self::$services = [];
        self::$calls = [];
    }

    private static function resolve(string $service, mixed ...$arguments): mixed
    {
        self::$calls[] = [$service, ...$arguments];
        $resolved = self::$services[$service] ?? throw new \Exception("Fake consumer has no {$service} service.");

        return $resolved instanceof \Throwable ? throw $resolved : $resolved;
    }
}
