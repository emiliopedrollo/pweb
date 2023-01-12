<?php

namespace App\Middlewares;

use App\Request;

abstract class Middleware
{
    public function handle(Request $request): void {}
    public function after(Request $request, mixed &$response): void {}
}
