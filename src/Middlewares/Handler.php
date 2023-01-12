<?php

namespace App\Middlewares;

use App\Application;
use App\Request;
use Exception;

class Handler
{

    public function __construct(
        protected readonly Application $app,
        protected readonly Request $request,
    ) { }

    protected array $defaultMiddlewares = [
        'bind_parameters'
    ];

    /**
     * @var array<string, class-string>
     */
    protected array $middlewares = [
        'start_session' => StartSession::class,
        'authenticated' => Authenticated::class,
        'bind_parameters' => BindParameters::class,
        'guest' => Guest::class,
    ];

    protected array $order = [
        StartSession::class,
        Authenticated::class,
        BindParameters::class,
    ];

    protected function sortMiddleware($middlewares): array
    {
        $lastIndex = 0;
        foreach ($middlewares as $index => $middleware) {
            $priorityIndex = array_search($middleware, $this->order);
            if ($priorityIndex !== false) {
                if (isset($lastPriorityIndex) && $priorityIndex < $lastPriorityIndex) {
                    array_splice($middlewares, $lastIndex, 0, $middlewares[$index]);
                    unset($middlewares[$index + 1]);
                    return $this->sortMiddleware(array_values($middlewares));
                }
                $lastIndex = $index;
                $lastPriorityIndex = $priorityIndex;
            }
        }
        return array_unique($middlewares);
    }


    /**
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    public function handleRequestMiddlewares(Request $request): mixed {

        /** @var Middleware[] $middlewares */
        $middlewares = array_map(
            fn($middleware) => app($middleware),
            $this->sortMiddleware(array_map(
                fn($middleware) => $this->middlewares[$middleware] ?? $middleware,
                array_reverse(array_unique(array_merge(
                    $this->defaultMiddlewares, $request->getMiddlewares()
                )))
            ))
        );

        foreach ($middlewares as $middleware) {
            $middleware->handle($request);
        }

        $response = $request->process();

        $middlewares = array_reverse($middlewares);
        foreach ($middlewares as $middleware) {
            $middleware->after($request, $response);
        }

        return $response;
    }

}
