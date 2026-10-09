<?php

namespace Taurus\Workflow\Consumer\Common\CreateRecord;

/**
 * Shared CREATE_RECORD behaviour for every consumer (Taurus, Nova, Mocha).
 *
 * A consumer only has to extend this class and declare which handler creates
 * which module, e.g. ['agentTask' => AgentTaskHandler::class].
 */
abstract class AbstractCreateRecordService
{
    /**
     * Map of CREATE_RECORD module => handler class.
     *
     * @return array<string, class-string<CreateRecordHandlerInterface>>
     */
    abstract protected function handlers(): array;

    public function execute(string $module, array $payload, array $context = []): mixed
    {
        return $this->resolveHandler($module)->execute($payload, $context);
    }

    public function supports(string $module): bool
    {
        return isset($this->handlers()[$module]);
    }

    private function resolveHandler(string $module): CreateRecordHandlerInterface
    {
        $handlerClass = $this->handlers()[$module] ?? null;

        if (! $handlerClass) {
            throw new \InvalidArgumentException("Create record is not supported for module: {$module}");
        }

        $handler = new $handlerClass;

        if (! $handler instanceof CreateRecordHandlerInterface) {
            throw new \LogicException("{$handlerClass} must implement ".CreateRecordHandlerInterface::class);
        }

        return $handler;
    }
}
