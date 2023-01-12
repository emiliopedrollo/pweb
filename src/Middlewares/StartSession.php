<?php

namespace App\Middlewares;

use App\Repositories\UserRepository;
use App\Request;
use App\Session;
use App\View;
use Exception;

class StartSession extends Middleware
{
    /**
     * @throws Exception
     */
    public function handle(Request $request): void
    {
        app(Session::class)->startup();

        if (Session::has('user')) {
            $user = (new UserRepository())->find(Session::get('user'));
            $request->setUser($user);
            app(View::class)->setAuth($user);
        }
    }

    public function after(Request $request, mixed &$response): void
    {
        app(Session::class)->cleanup();
    }


}
