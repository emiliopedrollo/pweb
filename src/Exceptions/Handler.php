<?php

namespace App\Exceptions;

use App\Application;
use App\Request;
use App\Response;

class Handler
{
    /** @noinspection PhpPropertyOnlyWrittenInspection */
    public function __construct(
        private readonly Application $app,
        private readonly ?Request $request
    ) {}

    public function handle(\Throwable $exception): Response {

        if ($exception instanceof AuthenticatedException) {
            return Response::redirect('/login');
        } elseif ($exception instanceof NotFoundException) {
            return Response::make(view('404'))->setCode(404);
        } elseif ($exception instanceof GuestOnlyRouteException) {
            return Response::redirect('/');
        }

        header('Internal Server Error', response_code: 500);
        return view('500',[
            'exception' => $exception
        ]);
    }

    public function handler(\Throwable $exception) {
        if (ob_get_status()) {
            ob_end_clean();
        }
        echo app(Handler::class)->handle($exception);
    }

    public function register() {
        set_error_handler( fn (...$args) => $this->handler(new \ErrorException(
            message: $args[1], code: $args[0], filename: $args[2], line: $args[3]
        )));
        set_exception_handler( fn (...$args) => $this->handler(...$args));
    }

}
