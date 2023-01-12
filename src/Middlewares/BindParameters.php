<?php

namespace App\Middlewares;

use App\Exceptions\AuthenticatedException;
use App\Exceptions\GuestOnlyRouteException;
use App\Repositories\UserRepository;
use App\Request;
use App\Router;
use App\Session;
use App\View;
use Exception;
use ReflectionException;
use ReflectionFunction;

class BindParameters extends Middleware
{
    /**
     * @throws Exception
     */
    public function handle(Request $request): void
    {

        $controller = $request->getController();
        if ($controller) {

            $matches = $request->getMatches();

            $controllerRequiresRequest = true;
            if (is_callable($controller)) {
                $method = new ReflectionFunction($controller(...));

                $parameters = $method->getParameters();
                $paramOffset = +1;

                $controllerRequiresRequest = false;
                foreach ($parameters as $index => $parameter) {
                    $parameterName = $parameter->getName();
                    $allowsNull = $parameter->allowsNull();

                    if ($parameterName === 'request') {
                        $controllerRequiresRequest = true;
                        $paramOffset = 0;
                        continue;
                    }

                    $matches[$parameterName] =
                        app(Router::class)->bind(
                            name: $parameterName,
                            value: $matches[$index + $paramOffset] ?? null,
                            nullable: $allowsNull
                        );
                }
            }

            $request
                ->setUriParameters($matches)
                ->setControllerRequiresRequest($controllerRequiresRequest);
        }
    }

    public function after(Request $request, mixed &$response): void
    {
//        var_dump('finish_auth');
    }


}
