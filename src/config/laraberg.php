<?php

use aruszala\Laraberg\Models\Block;
use aruszala\Laraberg\Models\Content;

return [
    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    */

    'use_package_routes' => true,

    'middlewares' => ['web', 'auth'],

    'prefix' => 'laraberg',

    'locale' => 'en_US',

    "models" => [
        "block" => Block::class,
        "content" => Content::class,
    ],

];
