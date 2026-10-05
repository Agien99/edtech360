<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/students', [StudentController::class, 'index'])->name('students.index');
Route::get('/students/register', [StudentController::class, 'create'])->name('students.create');
Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
Route::get('/profile/complete', [ProfileController::class, 'complete'])->name('profile.complete');
Route::get('/login', [AuthController::class, 'login'])->name('login');
