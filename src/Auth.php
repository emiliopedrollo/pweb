<?php

namespace App;

use App\Models\User;

class Auth
{
    public static User|null $user = null;
    public static function user(): ?User
    {
        return self::$user;
    }
}
