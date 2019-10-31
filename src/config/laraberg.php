<?php

use VanOns\Laraberg\Models\Block;
use VanOns\Laraberg\Models\Content;

return [
    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    */

    'use_package_routes' => true,

    'middlewares' => ['web', 'auth'],

    'prefix' => 'laraberg',

    'media_manager' => 'unisharp/laravel-filemanager',

    "models" => [
        "block" => Block::class,
        "content" => Content::class,
    ],

];
