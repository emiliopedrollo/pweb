<?php

namespace App;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class Application
{
    protected ?Config $config = null;

    protected array $binds = [];
    protected array $singletons = [];

    /**
     * @var string[]
     */
    protected array $aliases = [
        'request' => Request::class,
        'app' => Application::class,
        'config' => Config::class,
        'session' => Session::class,
    ];

    protected array $concretes = [];

    protected readonly string $base_path;

    public function __construct(string $base_path) {
        $this->base_path = rtrim($base_path, '\\/');
    }

    /**
     * @throws ReflectionException
     */
    public function init(): void
    {
        $this->get(Config::class)->parse();
    }

    /**
     * @param string|null $path
     * @return string
     */
    public function getBasePath(string $path = null): string
    {
        return rtrim($this->base_path . DIRECTORY_SEPARATOR . $path, DIRECTORY_SEPARATOR);
    }

    public function getViewsPath(): string
    {
        return $this->getBasePath(config('app.views', 'views'));
    }


    public function getCachePath(): string
    {
        return $this->getBasePath(config('app.cache', 'cache'));
    }

    public function bind (string $abstract, $concrete): void {
        $this->binds[$abstract] = $concrete;
    }

    public function singleton (string $abstract, $concrete): void {
        $this->singletons[$abstract] = $concrete;
    }

    protected function bindExists(string $abstract): bool {
        return array_key_exists($abstract, $this->binds);
    }

    protected function aliasesExists(string $alias): bool {
        return array_key_exists($alias, $this->aliases);
    }

    /**
     * @throws ReflectionException
     */
    protected function getMethodInjectArguments(ReflectionMethod|ReflectionFunction|null $method, ...$arguments): array
    {
        $default_arguments = [];
        $parameters = $method?->getParameters();
        if (!is_null($parameters)) {
            foreach ($parameters as $parameter) {

                if ($parameter->hasType()) {
                    $type = $parameter->getType();
                    if ($type and !$type->isBuiltin() and ($value = $this->get($type->getName()))) {
                        $default_arguments[$parameter->getName()] = $value;
                        continue;
                    }
                }

                if ($value = $this->get($parameter->getName())) {
                    $default_arguments[$parameter->getName()] = $value;
                    continue;
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $default_arguments[$parameter->getName()] = $parameter->getDefaultValue();
                }
            }
            $arguments = array_merge($default_arguments, ...$arguments);
        }
        return $arguments;
    }

    /**
     * @param callable $method
     * @param mixed ...$arguments
     * @return mixed
     * @throws ReflectionException
     */
    public function injectMethod(callable $method, ...$arguments): mixed
    {
        $reflection = new ReflectionFunction($method);
        $arguments = $this->getMethodInjectArguments($reflection, $arguments);
        return call_user_func($method, ...$arguments);
    }

    /**
     * @param class-string $class
     * @param mixed ...$arguments
     * @return mixed
     * @throws ReflectionException
     */
    public function injectConstructor(string $class, ...$arguments): mixed
    {
        $reflection = new ReflectionClass($class);
        if ($reflection->isInstantiable()) {
            $constructorArguments = $this->getMethodInjectArguments($reflection->getConstructor(), ...$arguments);
            return $reflection->newInstance(...$constructorArguments);
        }
        return null;
    }

    /**
     * @throws ReflectionException
     */
    public function get(string $abstract, $default = null, array $arguments = []): mixed
    {

        while ($this->aliasesExists($abstract)) {
            $abstract = $this->aliases[$abstract];
        }

        $singleton = false;
        if (isset($this->singletons[$abstract])) {
            $singleton = true;
        }

        if (!$singleton and isset($this->concretes[$abstract])) {
            return $this->concretes[$abstract];
        }

        $found = false;

        if ($this->binds[$abstract] ?? false) {
            $concrete = $this->binds[$abstract];
            $found = true;
        } else {
            $concrete = $abstract;
        }

        if (is_string($concrete) and class_exists($concrete)) {
                $this->concretes[$abstract] = $this->injectConstructor($concrete, ...$arguments);
                $concrete = $this->concretes[$abstract];
                $found = true;
        } elseif (is_callable($concrete)) {
            $this->concretes[$abstract] = $this->injectMethod($concrete, ...$arguments);
            $concrete = $this->concretes[$abstract];
            $found = true;
        }

        return $found ? $concrete ?? $default : $default;
    }

    public function config($args): Config
    {
        return ($this->config ??= app(Config::class))->get($args);
    }
}
