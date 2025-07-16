<?php

namespace Taurus\Workflow\Consumer\Taurus;

class InitInstance
{
    /**
     * Retrieves the module service for the specified module.
     *
     * @param string $module The name of the module(with namespace) for which the service is to be retrieved.
     * @return mixed The module service instance associated with the specified module.
     */
    public function getModuleService($module)
    {
        $module = $this->getModuleName($module);
        $moduleServiceClass = "Taurus\\Workflow\\Consumer\\Taurus\\Modules\\{$module}Service";

        if (class_exists($moduleServiceClass)) {
            return new $moduleServiceClass();
        } else {
            throw new \Exception("Module service class '$moduleServiceClass' does not exist.");
        }
    }

    /**
     * Retrieves the GraphQL query mapping for a specified module.
     *
     * @param string $module The name of the module(with namespace) for which to retrieve the query mapping.
     * @return mixed An associative array representing the GraphQL query mapping for the specified module.
     */
    public function getGraphQLQueryMappingService($module)
    {
        $module = $this->getModuleName($module);
        $graphQLQueryMappingClass = "Taurus\\Workflow\\Consumer\\Taurus\\GraphQL\\SchemaFieldAvailableToFetch\\{$module}";

        if (class_exists($graphQLQueryMappingClass)) {
            return new $graphQLQueryMappingClass();
        } else {
            throw new \Exception("Graph QL query mapping class '$graphQLQueryMappingClass' does not exist.");
        }
    }

    /**
     * Retrieves the name of the specified module.
     * Removes the namespace from the module name to return only the class name.
     *
     * @param mixed $module The module for which the name is to be retrieved.
     * @return string The name of the module.
     */
    private function getModuleName($module)
    {
        return last(explode('\\', $module));
    }


    public function getPostActionService()
    {
        $postActionServiceClass = "Taurus\\Workflow\\Consumer\\Taurus\\PostAction\\PostActionService";

        if (class_exists($postActionServiceClass)) {
            return new $postActionServiceClass();
        } else {
            throw new \Exception("Post action service class '$postActionServiceClass' does not exist.");
        }
    }
}
