<?php

use App\Autoloader;

require __DIR__ . '/Autoloader.php';

return Autoloader::register([
    'psr-4' => [
        'App\\' => './'
    ],
    'files' => [
        "helper.php"
    ]
]);
