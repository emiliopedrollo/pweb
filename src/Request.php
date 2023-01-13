<?php

namespace App;

use App\Exceptions\NotFoundException;
use App\Middlewares\Handler as HandlerAlias;
use App\Middlewares\Middleware;
use App\Models\User;
use Exception;

class Request
{
    public readonly string $uri;
    private ?array $uri_parameters = null;
    protected string $method;
    protected array $middlewares = [];
    protected ?User $user = null;

    protected bool $controllerRequiresRequest = true;

    protected ?Route $route = null;

    /**
     * @var callable|null
     */
    private $controller = null;
    protected array $matches = [];

    public function __construct(
        protected readonly array $get,
        protected readonly array $post,
        protected readonly array $server,
    )
    {
        list($this->uri) = explode('?',$server['REQUEST_URI']);
        $this->method = $post['_method'] ?? $server['REQUEST_METHOD'];
//        $this->middlewares = [
//            'bind_parameters'
//        ];
    }

    public static function generate(): static {
        return new Request(
            get: $_GET,
            post: $_POST,
            server: $_SERVER
        );
    }

    public function setUriParameters($parameters): static
    {
        $this->uri_parameters = array_intersect_key(
            $parameters, array_flip(array_filter(array_keys($parameters), fn($key) => !is_numeric($key)))
        );
        return $this;
    }

    public function addMatches($matches): static
    {
        $this->matches = $matches;
        return $this;
    }

    public function getMatches(): array
    {
        return $this->matches;
    }

    public function setMiddlewares(array $middlewares): static
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function addMiddlewares(array $middlewares): static
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * @return class-string<Middleware>[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function input($input = null, $default = null): mixed {
        $result = array_merge(
            $this->get(),
            $this->post(),
            $this->getUriParameters() ?? []
        );
        return ($input) ? $result[$input] ?? $default : $result;
    }

    public function get($input = null, $default = null): mixed
    {
        $result = $this->get ?? [];
        return ($input) ? $result[$input] ?? $default : $result;
    }

    public function post($input = null, $default = null): mixed
    {
        $result = $this->post ?? [];
        return ($input) ? $result[$input] ?? $default : $result;
    }

    public function getMethod(): string {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getUriParameters(): array
    {
        return $this->uri_parameters ?? [];
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        echo app(HandlerAlias::class)->handleRequestMiddlewares($this);
    }

    public function setController(?callable $controller): static
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function process(): mixed
    {
        $controller = $this->controller;
        if ($controller) {

            if ($this->controllerRequiresRequest) {
                return call_user_func($controller, $this, ...$this->getUriParameters());
            } else {
                return call_user_func($controller, ...$this->getUriParameters());
            }


        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @return callable|null
     */
    public function getController(): ?callable
    {
        return $this->controller;
    }

    public function isAuthenticated(): bool
    {
        return !is_null($this->user);
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param Route $route
     * @return Request
     */
    public function setRoute(Route $route): static
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @return Route|null
     */
    public function getRoute(): ?Route
    {
        return $this->route;
    }

    /**
     * @param bool $controllerRequiresRequest
     * @return Request
     */
    public function setControllerRequiresRequest(bool $controllerRequiresRequest): Request
    {
        $this->controllerRequiresRequest = $controllerRequiresRequest;
        return $this;
    }
}
