<?php

namespace App;

use App\Exceptions\NotFoundException;
use App\Models\Post;
use App\Repositories\Repository;
use Exception;
use ReflectionException;

class Router
{
    /**
     * @param Route[] $routes
     */
    public function __construct(
        private readonly array $routes
    ) {}

    /**
     * @throws ReflectionException
     * @throws NotFoundException
     */
    public function parse(Request $request) {
        foreach ($this->routes as $route) {
            if ($route->match($request)) {
                $request
                    ->addMiddlewares($route->getMiddlewares())
                    ->setController($route->getController());
                break;
            }
        }
    }

    /**
     * @throws NotFoundException
     */
    protected function defaultBind($name, $value, $nullable): mixed
    {
        /** @var class-string<Repository>|Repository $repository */
        $repository = sprintf('App\\Repositories\\%sRepository', ucfirst($name));

        $model = class_exists($repository)
            ? app($repository)->find($value)
            : null;

        if (is_null($model) and !$nullable) {
            throw new NotFoundException();
        }

        return $model;
    }

    /**
     * @throws Exception
     */
    public function bind($name, $value, $nullable = false): mixed {
        $request = app(Request::class);

        return match ($name) {
            'post' => Post::query()
                ->when($request->isAuthenticated(), fn(Query $query) =>
                    $query->where('user_id', '=', $request->getUser()->id)
                )
                ->where('id', '=', $value)
                ->first(orFail: !$nullable),
            default => $this->defaultBind($name, $value, $nullable),
        };
    }
}
