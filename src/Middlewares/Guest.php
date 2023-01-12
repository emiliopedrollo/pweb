<?php

namespace App\Middlewares;

use App\Exceptions\AuthenticatedException;
use App\Exceptions\GuestOnlyRouteException;
use App\Repositories\UserRepository;
use App\Request;
use App\Session;
use App\View;

class Guest extends Middleware
{
    /**
     * @throws GuestOnlyRouteException
     */
    public function handle(Request $request): void
    {
        if ($request->isAuthenticated()) {
            throw new GuestOnlyRouteException();
        }
    }

    public function after(Request $request, mixed &$response): void
    {
//        var_dump('finish_auth');
    }


}
