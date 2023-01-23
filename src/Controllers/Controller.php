<?php

namespace App\Controllers;

use App\Application;

class Controller
{
    public function __construct(
        private readonly Application $app
    ) {

    }

    /**
     * @return Application
     */
    public function app(): Application
    {
        return $this->app;
    }
}
