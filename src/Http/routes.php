<?php

    Route::group(['prefix' => config('laraberg.prefix'), 'middleware' => config('laraberg.middlewares')], function () {
        Route::apiResource('blocks', 'VanOns\Laraberg\Http\Controllers\BlockController');
        Route::get('oembed', 'VanOns\Laraberg\Http\Controllers\OEmbedController');
    });

    Route::group(['middleware' => config('laraberg.middlewares')], function () {
        Route::get('laraberg/i18n', 'VanOns\Laraberg\Http\Controllers\ApplicationController@getTranslations');
    });
