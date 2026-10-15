<?php

use App\Http\Controllers\ClassChangeWindowController;
use App\Http\Controllers\StudentSelfClassChangeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('/class-change-windows', [ClassChangeWindowController::class, 'index'])
        ->middleware('can:academic_sessions.manage')->name('class-change-windows.index');
    Route::put('/class-change-windows/{academicSession}', [ClassChangeWindowController::class, 'update'])
        ->middleware('can:academic_sessions.manage')->name('class-change-windows.update');
    Route::get('/my/class-change', [StudentSelfClassChangeController::class, 'index'])->name('student.class-change.index');
    Route::post('/my/class-change', [StudentSelfClassChangeController::class, 'store'])->name('student.class-change.store');
});
