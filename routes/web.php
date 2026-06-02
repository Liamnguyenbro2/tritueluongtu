<?php

use App\Http\Controllers\AdminAnnouncementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminEmailOtpController;
use App\Http\Controllers\AdminLessonController;
use App\Http\Controllers\AdminPlanController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AccountantController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankWebhookController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForgotPasswordOtpController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProtectedLessonMediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserTransactionController;
use App\Http\Controllers\VoiceSampleController;
use App\Http\Controllers\WalletController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');
Route::post('/hooks/payment', BankWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('hooks.payment');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::get('/register/referral', [AuthController::class, 'lookupReferral'])->name('register.referral.lookup');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [ForgotPasswordOtpController::class, 'show'])->name('password.forgot');
    Route::post('/forgot-password/send-otp', [ForgotPasswordOtpController::class, 'sendOtp'])->name('password.forgot.send-otp');
    Route::post('/forgot-password/verify-otp', [ForgotPasswordOtpController::class, 'verifyOtp'])->name('password.forgot.verify-otp');
    Route::post('/forgot-password/reset', [ForgotPasswordOtpController::class, 'reset'])->name('password.forgot.reset');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'not_suspended'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profile/bank-account', [ProfileController::class, 'updateBank'])->name('profile.bank-account');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/announcements/{announcement}/read', [NotificationController::class, 'readAnnouncement'])->name('announcements.read');
    Route::get('/members', [AffiliateController::class, 'index'])->name('affiliate.index');
    Route::post('/lessons/{lesson}/toggle', [DashboardController::class, 'toggle'])->name('lessons.toggle');
    Route::get('/lessons/{lesson}/thumbnail', [ProtectedLessonMediaController::class, 'thumbnail'])->name('lessons.thumbnail');
    Route::get('/lessons/{lesson}/media', [ProtectedLessonMediaController::class, 'media'])->name('lessons.media');
    Route::get('/lessons/{lesson}/player', [ProtectedLessonMediaController::class, 'player'])->name('lessons.player');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/orders', [BillingController::class, 'store'])->name('billing.orders.store');
    Route::get('/billing/orders/{order}', [BillingController::class, 'show'])->name('billing.orders.show');
    Route::post('/voice-sample', [VoiceSampleController::class, 'store'])->name('voice-sample.store');
    Route::post('/voice-sample/complete', [VoiceSampleController::class, 'complete'])->name('voice-sample.complete');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::get('/user/transactions', [UserTransactionController::class, 'index'])->name('transactions.index');
    Route::post('/wallet/bank-account', [WalletController::class, 'saveBankAccount'])->name('wallet.bank-account');
    Route::post('/wallet/withdrawals', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/shared-pool', [AdminController::class, 'sharedPoolHistory'])->name('shared-pool.history');
    Route::put('/branding', [AdminController::class, 'updateBranding'])->name('branding.update');
    Route::post('/wallet-transfer', [AdminController::class, 'transferToUser'])->name('wallet-transfer');
    Route::get('/passwords', [AdminController::class, 'passwords'])->name('passwords');
    Route::post('/passwords', [AdminController::class, 'updateUserPassword'])->name('passwords.update');
    Route::get('/notifications', [AdminAnnouncementController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/marquee', [AdminAnnouncementController::class, 'updateMarquee'])->name('notifications.marquee.update');
    Route::put('/notifications/fixed', [AdminAnnouncementController::class, 'updateFixed'])->name('notifications.fixed.update');
    Route::post('/notifications/campaigns', [AdminAnnouncementController::class, 'storeCampaign'])->name('notifications.campaigns.store');
    Route::post('/notifications/{announcement}/toggle', [AdminAnnouncementController::class, 'toggle'])->name('notifications.toggle');
    Route::get('/email-otp', [AdminEmailOtpController::class, 'index'])->name('email-otp.index');
    Route::put('/email-otp/smtp', [AdminEmailOtpController::class, 'updateSmtp'])->name('email-otp.smtp.update');
    Route::post('/email-otp/test-connection', [AdminEmailOtpController::class, 'testConnection'])->name('email-otp.smtp.test');
    Route::post('/email-otp/send-test-email', [AdminEmailOtpController::class, 'sendTestEmail'])->name('email-otp.smtp.send-test');
    Route::put('/email-otp/template', [AdminEmailOtpController::class, 'updateTemplate'])->name('email-otp.template.update');
    Route::post('/email-otp/template/restore', [AdminEmailOtpController::class, 'restoreTemplate'])->name('email-otp.template.restore');
    Route::post('/accountants', [AdminController::class, 'storeAccountant'])->name('accountants.store');
    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/{plan}', [AdminPlanController::class, 'show'])->name('plans.show');
    Route::put('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{snapshot}/export', [AdminReportController::class, 'export'])->name('reports.export');
    Route::get('/lessons', [AdminLessonController::class, 'index'])->name('lessons.index');
    Route::post('/lessons', [AdminLessonController::class, 'store'])->name('lessons.store');
    Route::put('/lessons/{lesson}', [AdminLessonController::class, 'update'])->name('lessons.update');
    Route::post('/lessons/{lesson}/delete-media', [AdminLessonController::class, 'deleteMedia'])->name('lessons.delete-media');
    Route::get('/users/{user}/report', [AdminController::class, 'report'])->name('users.report');
    Route::put('/users/{user}/basic-info', [AdminController::class, 'updateBasicInfo'])->name('users.basic-info.update');
    Route::post('/users/{user}/suspend', [AdminController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/unlock', [AdminController::class, 'unlock'])->name('users.unlock');
    Route::post('/users/{user}/unlock-bank', [AdminController::class, 'unlockBank'])->name('users.unlock-bank');
    Route::post('/withdrawals/{withdrawal}/approve', [AdminController::class, 'approveWithdrawal'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/reject', [AdminController::class, 'rejectWithdrawal'])->name('withdrawals.reject');
});

Route::middleware(['auth', 'accountant'])->prefix('accountant')->name('accountant.')->group(function () {
    Route::get('/', [AccountantController::class, 'dashboard'])->name('dashboard');
    Route::get('/transactions', [AccountantController::class, 'transactions'])->name('transactions.index');
    Route::get('/transactions/export/{format}', [AccountantController::class, 'exportTransactions'])->name('transactions.export');
    Route::get('/transactions/{transaction}', [AccountantController::class, 'transactionShow'])->name('transactions.show');
    Route::get('/transactions/{transaction}/invoice', [AccountantController::class, 'transactionInvoice'])->name('transactions.invoice');
    Route::get('/withdrawals', [AccountantController::class, 'withdrawals'])->name('withdrawals.index');
    Route::post('/withdrawals/{withdrawal}/approve', [AccountantController::class, 'approveWithdrawal'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/reject', [AccountantController::class, 'rejectWithdrawal'])->name('withdrawals.reject');
    Route::post('/withdrawals/{withdrawal}/mark-transferred', [AccountantController::class, 'markTransferred'])->name('withdrawals.mark-transferred');
    Route::post('/withdrawals/{withdrawal}/resend', [AccountantController::class, 'resendTransfer'])->name('withdrawals.resend');
    Route::get('/deposits', [AccountantController::class, 'deposits'])->name('deposits.index');
    Route::get('/wallets', [AccountantController::class, 'wallets'])->name('wallets.index');
    Route::post('/wallets/{user}/adjust', [AccountantController::class, 'adjustWallet'])->name('wallets.adjust');
    Route::post('/wallets/{user}/toggle-lock', [AccountantController::class, 'toggleWalletLock'])->name('wallets.toggle-lock');
    Route::get('/revenue', [AccountantController::class, 'revenue'])->name('revenue');
    Route::get('/reports', [AccountantController::class, 'reports'])->name('reports');
    Route::get('/reports/export/{format}', [AccountantController::class, 'exportReports'])->name('reports.export');
    Route::get('/audit-logs', [AccountantController::class, 'auditLogs'])->name('audit-logs');
});
