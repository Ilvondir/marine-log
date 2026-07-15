<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminObservationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ObservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ObservationController::class, 'index'])->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'createLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'storeLogin'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'storeRegister'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Public observation routes (no auth required)
Route::get('/observations', [ObservationController::class, 'index'])->name('observations.index');

// Authenticated observation routes (before wildcard to avoid conflict)
Route::middleware('auth')->group(function (): void {
    Route::get('/observations/my', [ObservationController::class, 'myObservations'])->name('observations.my');
    Route::get('/observations/create', [ObservationController::class, 'create'])->name('observations.create');
    Route::post('/observations', [ObservationController::class, 'store'])->name('observations.store');
    Route::get('/observations/{observation}/edit', [ObservationController::class, 'edit'])->name('observations.edit');
    Route::put('/observations/{observation}', [ObservationController::class, 'update'])->name('observations.update');
    Route::delete('/observations/{observation}', [ObservationController::class, 'destroy'])->name('observations.destroy');
});

// Public show (after /create to avoid wildcard conflict)
Route::get('/observations/{observation}', [ObservationController::class, 'show'])->name('observations.show');

// Admin area
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/observations', [AdminObservationController::class, 'index'])->name('observations.index');
    Route::delete('/observations/{observation}', [AdminObservationController::class, 'destroy'])->name('observations.destroy');
    Route::patch('/observations/{observation}/unpublish', [AdminObservationController::class, 'unpublish'])->name('observations.unpublish');
});
