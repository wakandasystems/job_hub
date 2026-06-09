<?php

use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;

Theme::registerRoutes(function (): void {
    Route::group(['namespace' => 'Botble\DpoGroup\Http\Controllers'], function (): void {
        Route::get('dpo-group/payment/callback', [
            'as' => 'dpo-group.payment.callback',
            'uses' => 'DpoGroupController@callback',
        ]);
        Route::get('dpo-group/payment/cancel', [
            'as' => 'dpo-group.payment.cancel',
            'uses' => 'DpoGroupController@cancel',
        ]);
    });
});
