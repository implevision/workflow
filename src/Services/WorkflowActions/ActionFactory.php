<?php

namespace Taurus\Workflow\Services\WorkflowActions;

class ActionFactory
{
    /**
     * Map of action type identifiers to their implementing classes.
     * Add new actions here to make them available across all dispatch services.
     */
    protected static array $actionMap = [
        'EMAIL' => EmailAction::class,
        'WEB_HOOK' => WebhookAction::class,
    ];

    /**
     * Create and return an action instance for the given type.
     *
     * @throws \InvalidArgumentException If the action type is not registered.
     */
    public static function create(string $actionType, array $actionPayload): AbstractWorkflowAction
    {
        if (! isset(static::$actionMap[$actionType])) {
            throw new \InvalidArgumentException("Unsupported action type: {$actionType}");
        }

        $actionClass = static::$actionMap[$actionType];

        return new $actionClass($actionType, $actionPayload);
    }

    /**
     * Register a new action type at runtime.
     */
    public static function register(string $actionType, string $actionClass): void
    {
        static::$actionMap[$actionType] = $actionClass;
    }

    /**
     * Check whether an action type is supported.
     */
    public static function supports(string $actionType): bool
    {
        return isset(static::$actionMap[$actionType]);
    }
}
