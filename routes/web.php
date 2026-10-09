<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UiDemoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentManagementController;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::get('/dashboard', [UiDemoController::class, 'dashboard'])
        ->middleware('can:dashboard.view')
        ->name('dashboard');

    Route::get('/students', [UiDemoController::class, 'students'])
        ->middleware('can:students.view')
        ->name('students.index');

    Route::get('/students/register', [UiDemoController::class, 'registerStudent'])
        ->middleware('can:students.create')
        ->name('students.create');

    Route::get('/students/complete-profile', [UiDemoController::class, 'completeProfile'])
        ->middleware('can:students.update')
        ->name('students.complete-profile');

    Route::get('/classes', [UiDemoController::class, 'classes'])
        ->middleware('can:classes.view')
        ->name('classes.index');

    Route::get('/subjects', [UiDemoController::class, 'subjects'])
        ->middleware('can:subjects.view')
        ->name('subjects.index');

        Route::patch(
        '/students/{student}/profile',
        [StudentManagementController::class, 'update']
    )->name('students.profile.update');

    Route::post(
        '/students/{student}/transfer',
        [StudentManagementController::class, 'transfer']
    )->name('students.transfer');
});
