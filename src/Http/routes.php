<?php

    Route::group(['prefix' => config('laraberg.prefix'), 'middleware' => config('laraberg.middlewares')], function () {
        Route::apiResource('blocks', 'aruszala\Laraberg\Http\Controllers\BlockController');
        Route::get('oembed', 'aruszala\Laraberg\Http\Controllers\OEmbedController');
        Route::get('i18n', 'aruszala\Laraberg\Http\Controllers\ApplicationController@getTranslations');
    });
