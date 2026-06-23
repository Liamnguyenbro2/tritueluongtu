<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankWebhookController;
use App\Http\Controllers\SepayWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/check-email', [AuthController::class, 'lookupEmail'])->name('register.email.lookup');
Route::get('/check-username', [AuthController::class, 'lookupUsername'])->name('register.username.lookup');
Route::post('/bank/webhook', BankWebhookController::class)->name('bank.webhook');
Route::post('/payment/sepay/webhook', [SepayWebhookController::class, 'handle'])->name('payment.sepay.webhook');
