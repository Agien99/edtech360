<?php
use App\Http\Controllers\StudentRegistrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'account.active', 'can:students.create'])
    ->prefix('student-registrations')->name('student-registrations.')
    ->group(function () {
        Route::get('/', [StudentRegistrationController::class, 'index'])->name('index');
        Route::post('/', [StudentRegistrationController::class, 'store'])->name('store');
        Route::post('/import', [StudentRegistrationController::class, 'import'])->name('import');
    });
