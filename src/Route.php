<?php

namespace App;

use Exception;
use ReflectionException;
use ReflectionFunction;

class Route
{
    /**
     * @var array<string|class-string>
     */
    protected array $middleware = [];

    public function __construct(
        protected string $method,
        protected string $route,
        protected mixed $controller,
    ){}

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function match(Request $request): bool
    {
        $route = '~^'.preg_replace('~\{(.*?)}~','(.*?)',rtrim($this->route,'/')).'$~';

        if (
            $this->matchMethod($request) and
            preg_match($route, rtrim($request->uri,'/'), $matches)
        ) {
            $request->setRoute($this);
            $request->addMatches($matches);
            return true;
        }
        return false;
    }

    protected function matchMethod(Request $request): bool
    {
        return $this->method === 'any' or $request->getMethod() === strtoupper($this->method);
    }

    public function getController(): ?callable
    {
        if (is_array($this->controller) and (sizeof($this->controller) === 2)) {
            $class = $this->controller[0];
            $method = $this->controller[1];
            $controller = app($class);
            return [$controller, $method];
        }

        if (is_callable($this->controller)){
            return fn(Request $request) => app()->injectMethod($this->controller,[
                'request' => $request
            ]);
        }

        if (is_string($this->controller)) {
            return fn() => $this->controller;
        }

        return null;
    }


    public static function Get(string $route, $controller): static
    {
        return new Route('GET',$route, $controller);
    }

    public static function Post(string $route, $controller): static
    {
        return new Route('POST',$route, $controller);
    }

    public static function Middleware(string|array $middlewares, array|callable $routes): array
    {
        if (is_string($middlewares)) {
            $middlewares = [$middlewares];
        }

        return static::Group([
            'middleware' => $middlewares
        ], $routes);
    }

    public static function Prefix(string $prefix, array|callable $routes): array
    {
        return static::Group([
            'prefix' => $prefix
        ], $routes);
    }

    /**
     * @param array $attributes
     * @param Route[]|callable $routes
     * @return array
     */
    public static function Group(array $attributes, array|callable $routes): array
    {
        if (is_callable($routes)) {
            $routes = call_user_func($routes);
        }

        /** @var Route[] $routes */
        $routes = array_flatten($routes);

        foreach ($attributes['middleware'] ?? [] as $middleware) {
            foreach ($routes as $route) {
                $route->addMiddleware($middleware);
            }
        }
        if ($attributes['prefix'] ?? false) {
            foreach ($routes as $route) {
                $route->addPrefix($attributes['prefix']);
            }
        }

        return $routes;
    }

    public function addMiddleware($middleware): Route
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function getMiddlewares(): array
    {
        return $this->middleware;
    }

    protected function addPrefix(string $prefix): static
    {
        $this->route = $prefix . $this->route;
        return $this;
    }


}
