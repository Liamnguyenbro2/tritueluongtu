<?php

use App\Http\Controllers\BankWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/bank/webhook', BankWebhookController::class)->name('bank.webhook');
