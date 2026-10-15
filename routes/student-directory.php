<?php
use App\Http\Controllers\StudentDirectoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','account.active'])->group(function () {
    Route::get('/students', [StudentDirectoryController::class, 'index'])->middleware('can:students.view')->name('students.index');
    Route::get('/students/{student}', [StudentDirectoryController::class, 'show'])->middleware('can:students.view')->name('students.show');
    Route::patch('/students/{student}/admin-details', [StudentDirectoryController::class, 'update'])->middleware('can:students.update')->name('students.admin.update');
    Route::patch('/students/{student}/account-status', [StudentDirectoryController::class, 'status'])->middleware('can:students.update')->name('students.admin.status');
});
