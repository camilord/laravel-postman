<?php

namespace Phpsa\LaravelPostman;

use Illuminate\Support\Str;
use Illuminate\Routing\Route;

class Helper
{
    const CONTROLLER_STRING_INDEX = 0;
    const POSTMAN_SCHEMA = 'https://schema.getpostman.com/json/collection/v2.0.1/collection.json';

    /**
     * Adds a trailing slash to the given path if it isn't already there
     *
     * @param  string $path
     * @return string
     */
    public function addTrailingSlash($path)
    {
        return $path . (Str::endsWith($path, '/') ?: '/');
    }

    /**
     * Replaces laravel route parameters format with Postman parameters format
     *
     * @param  string $path
     * @return string
     */
    public function replaceGetParameters($path)
    {
        return Str::replace(['{', '}'], [':', ''], $path);
    }

    /**
     * Returns the API base URL
     *
     * @return string
     */
    public function getBaseURL()
    {
        $configURL = config('postman.apiURL');

        if (! empty($configURL)) {
            return $this->addTrailingSlash($configURL);
        }

        return $this->addTrailingSlash(config('app.url'));
    }

    /**
     * Returns the API prefix string
     *
     * @return string
     */
     public function getApiPrefix($key = 'apiPrefix'): ?array
    {
        $apiPrefix = config('postman.' . $key, null);
        return ! blank($apiPrefix)
        ? collect(explode(",", $apiPrefix))->map(fn($str) => Str::of($str)->rtrim("/")->append("/")->toString())->toArray()
        : null;
    }

    /**
     * Returns a postman collection structure array
     *
     * @param  string $collectionName
     * @param  string $collectionDescription
     * @return array
     */
    public function getCollectionStructure(
        $collectionName,
        $collectionDescription
    ) {
        return [
            'variables' => [
                [
                    "id"    => uniqid(),
                    "key"   => "api_url",
                    "value" => $this->getBaseURL(),
                    "type"  => "string"
                ],
                [
                    "id"    => uniqid(),
                    "key"   => "bearer_token",
                    "value" => '',
                    "type"  => "string"
                ]
            ],
            "auth"      => [
                "type"   => "bearer",
                "bearer" => [
                    [
                        "key"   => "0",
                        "value" => [
                            "key"   => "token",
                            "value" => "bearer {{bearer_token}}",
                            "type"  => "string"
                        ],
                        "type"  => "any"
                    ],
                    [
                        "key"   => "token",
                        "value" => "bearer {{bearer_token}}",
                        "type"  => "string"
                    ]
                ]
            ],
            'info'      => [
                'name'        => $collectionName,
                '_postman_id' => uniqid(),
                'description' => $collectionDescription,
                'schema'      => self::POSTMAN_SCHEMA,
            ],
            'item'      => [],
        ];
    }

    /**
     * Obtains a postman folder name from the given laravel route
     *
     * @param  \Illuminate\Routing\Route $route
     * @return string
     */
    public function getRouteFolder(Route $route)
    {
        $path = $route->getPrefix();
        if(blank($path)) {
            $path = 'others';
        }
        $actionStringParts = explode('@', $route->getActionName());
        if (count($actionStringParts) === 1) {
            return 'Others';
        }

        $fullController = $actionStringParts[self::CONTROLLER_STRING_INDEX];
        $controllerClass = explode('\\', $fullController);

        return  str_replace('Controller', '', last($controllerClass));
    }

    /**
     * Returns the path where the exported file will be located
     *
     * @return string
     */
    public function getExportDirectory()
    {
        $exportDirectory = config('postman.exportDirectory');

        if (empty($exportDirectory)) {
            return $exportDirectory;
        }

        return $this->addTrailingSlash($exportDirectory);
    }

    /**
     * Finds out if a postman model can be get from the route
     *
     * @param  \Illuminate\Routing\Route $route
     * @return boolean
     */
    public function canGetPostmanModel($route)
    {
        if($route->getActionName() === 'Closure'){
            return false;
        }
        if (method_exists($route, 'getController')
            && is_object($route->getController())
            && (
                property_exists($route->getController(), 'postmanModel')
                || method_exists($route->getController(), 'getPostmanModel')
                )
        ) {
            return true;
        }

        if (method_exists($route, 'getAction')
            && is_array($route->getAction())
            && in_array('controller', array_keys($route->getAction()))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns a route's postman model
     *
     * @param  \Illuminate\Routing\Route $route
     * @return object|null
     */
    public function getPostmanModel($route)
    {

        if (! $this->canGetPostmanModel($route)) {
            return null;
        }

        if (method_exists($route, 'getController')) {
            if (property_exists($route->getController(), 'postmanModel')) {
                $postmanModelClass = $route->getController()->postmanModel;
                return new $postmanModelClass();
            }
            if (method_exists($route->getController(), 'getPostmanModel')) {
                $postmanModelClass = $route->getController()->getPostmanModel();
                return new $postmanModelClass();
            }
        }

        $action = $route->getAction();
        $controllerAction = explode('@', $action['controller']);
        $controllerClass = $controllerAction[0];
        $controller = app($controllerClass);

        if (property_exists($controller, 'postmanModel')) {
            $postmanModelClass = $controller->postmanModel;
            return new $postmanModelClass();
        }

        if (method_exists($controller, 'getPostmanModel')) {
            $postmanModelClass = $controller->getPostmanModel();
            return new $postmanModelClass();
        }

        return null;
    }
}
