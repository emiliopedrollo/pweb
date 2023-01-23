<?php

use App\Controllers\Auth;
use App\Controllers\Comment;
use App\Controllers\Post;
use App\Route;

return [

    Route::Redirect('/','/login'),

    Route::Middleware('start_session',[

        Route::Middleware('guest',[
            Route::Get('/login',[Auth::class, 'login'])->named('login'),
            Route::Post('/login',[Auth::class, 'authenticate'])->named('signin'),
            Route::Get('/register',[Auth::class, 'register'])->named('register'),
            Route::Post('/register',[Auth::class, 'signup'])->named('signup'),
        ]),

        Route::Any('/logout',[Auth::class, 'logout'])->named('logout'),


        Route::Middleware('authenticated',[
            Route::Prefix('/posts',[
                Route::Get('/',[Post::class, 'index'])->named('posts'),
                Route::Get('/create',[Post::class, 'create'])->named('post.create'),
                Route::Post('/store',[Post::class, 'store'])->named('post.store'),

                Route::Prefix('/{post}', [
                    Route::Get('/',[Post::class, 'show'])->named('post.show'),
                    Route::Get('/edit',[Post::class, 'edit'])->named('post.edit'),
                    Route::Post('/',[Post::class, 'update'])->named('post.update'),
                    Route::Delete('/',[Post::class, 'destroy'])->named('post.destroy'),

                    Route::Prefix('/comments', [
                        Route::Post('/store',[Comment::class, 'store'])->named('comment.store'),
                        Route::Prefix('/{comment}', [
                            Route::Get('/edit',[Comment::class, 'edit'])->named('comment.edit'),
                            Route::Post('/',[Comment::class, 'update'])->named('comment.update'),
                            Route::Delete('/',[Comment::class, 'destroy'])->named('comment.destroy'),
                        ]),
                    ])

                ]),

            ]),

        ]),

    ])
];
