<?php

use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;

Theme::registerRoutes(function (): void {
    Route::group(['namespace' => 'Botble\Pesapal\Http\Controllers'], function (): void {
        Route::get('pesapal/payment/callback', [
            'as' => 'pesapal.payment.callback',
            'uses' => 'PesapalController@callback',
        ]);
        Route::get('pesapal/payment/ipn', [
            'as' => 'pesapal.payment.ipn',
            'uses' => 'PesapalController@ipn',
        ]);
    });
});
