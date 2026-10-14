<?php
use App\Http\Controllers\StudentEnrollmentManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('/student-enrollments', [StudentEnrollmentManagementController::class, 'index'])
        ->middleware('can:students.enroll')->name('student-enrollments.index');
    Route::post('/student-enrollments', [StudentEnrollmentManagementController::class, 'store'])
        ->middleware('can:students.enroll')->name('student-enrollments.store');
});
