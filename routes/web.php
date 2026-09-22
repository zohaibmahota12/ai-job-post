<?php

use App\Http\Controllers\Admin\SourceController as AdminSourceController;
use App\Http\Controllers\Admin\SourceRunController as AdminSourceRunController;
use App\Http\Controllers\Admin\SystemErrorController as AdminSystemErrorController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSkillController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\SavedOpportunityController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-email')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-email')
        ->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
});

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/skills', [ProfileSkillController::class, 'store'])->name('profile.skills.store');
    Route::delete('/profile/skills/{skill}', [ProfileSkillController::class, 'destroy'])->name('profile.skills.destroy');

    Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])->name('opportunities.show');
    Route::post('/opportunities/{opportunity}/save', [SavedOpportunityController::class, 'store'])->name('opportunities.save');

    Route::get('/saved', [SavedOpportunityController::class, 'index'])->name('saved.index');
    Route::delete('/saved/{savedOpportunity}', [SavedOpportunityController::class, 'destroy'])->name('saved.destroy');

    Route::resource('proposals', ProposalController::class)->except(['show']);
    Route::resource('applications', ApplicationController::class)->except(['show', 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::get('/sources', [AdminSourceController::class, 'index'])->name('sources.index');
        Route::get('/source-runs', [AdminSourceRunController::class, 'index'])->name('source-runs.index');
        Route::get('/errors', [AdminSystemErrorController::class, 'index'])->name('errors.index');
    });
});
