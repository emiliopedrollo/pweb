<?php

namespace App\Middlewares;

use App\Exceptions\AuthenticatedException;
use App\Repositories\UserRepository;
use App\Request;
use App\Session;
use App\View;

class Authenticated extends Middleware
{
    /**
     * @throws AuthenticatedException
     */
    public function handle(Request $request): void
    {
        if (!$request->isAuthenticated()) {
            throw new AuthenticatedException();
        }
    }

    public function after(Request $request, mixed &$response): void
    {
//        var_dump('finish_auth');
    }


}
