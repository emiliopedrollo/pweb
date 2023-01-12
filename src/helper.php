<?php

use App\Application;
use App\Enums\View\CompileFileName;
use App\View;
use App\Enums\View\Mode;
use App\Response;
//use eftec\bladeone\BladeOne;

if (!function_exists('array_flatten')) {
    function array_flatten(array $array): array {
        $return = [];
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }
}

if (!function_exists('app')) {
    function app(?string $abstract = null, $default = null, array $arguments = []): mixed {
        global $app;

        return is_null($abstract)
            ? $app
            : $app->get($abstract, $default, $arguments);
    }
}

if (!function_exists('env')) {
    function env($env, mixed $default = null): mixed {

        static $ini;
        $ini ??= parse_ini_file('.env');

        if (($value = getenv($env)) !== false) {
            return match (mb_strtolower($value)) {
                '1', 'true', 't' => true,
                '0', 'false', 'f' => false,
                default => $value
            };
        } elseif (array_key_exists($env,$ini)) {
            return $ini[$env];
        } else {
            return $default;
        }
    }
}

if (!function_exists('config')) {
    function config(...$args): mixed {
        return app('config')->get(...$args);
    }
}

if (!function_exists('view')) {

    function view(string $view, array $parameters = []): Response {
        return Response::make(app(View::class)
            ->setCompileTypeFileName(CompileFileName::normal)
            ->setMode(Mode::debug)
            ->run($view, $parameters));
    }

}
