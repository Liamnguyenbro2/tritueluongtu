<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/check-email', [AuthController::class, 'lookupEmail'])->name('register.email.lookup');
Route::get('/check-username', [AuthController::class, 'lookupUsername'])->name('register.username.lookup');
Route::post('/bank/webhook', BankWebhookController::class)->name('bank.webhook');
