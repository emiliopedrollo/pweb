<?php

use App\Controllers\Auth;
use App\Controllers\Index;
use App\Controllers\Post;
use App\Route;

return [
    Route::Middleware('start_session',[

        Route::Middleware('guest',[
            Route::Get('/login',[Auth::class, 'login']),
            Route::Post('/login',[Auth::class, 'authenticate']),
            Route::Get('/register',[Auth::class, 'register']),
            Route::Post('/register',[Auth::class, 'register']),
        ]),

        Route::Get('/logout',[Auth::class, 'logout']),


        Route::Middleware('authenticated',[
            Route::Get('/test',[Index::class, 'index']),

            Route::Prefix('/posts',[
                Route::Get('/',[Post::class, 'index']),
                Route::Get('/{post}',[Post::class, 'show']),
            ]),

        ]),

        Route::Get('/(?<fuck>[a]{2,5})',[Index::class, 'index']),
        Route::Get('/',[Index::class, 'index']),
        Route::Get('/db',[Index::class, 'db']),

    ])
];
