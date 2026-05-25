<?php

use App\Http\Controllers\NewsletterPromoSubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/newsletter/promo-subscribe', [NewsletterPromoSubscribeController::class, 'subscribe'])
    ->name('newsletter.promo.subscribe');
