<?php
use App\Http\Controllers\UiDemoController; use Illuminate\Support\Facades\Route;
Route::redirect('/','/login');
Route::get('/login',[UiDemoController::class,'login'])->name('login');
Route::get('/dashboard',[UiDemoController::class,'dashboard'])->name('dashboard');
Route::get('/students',[UiDemoController::class,'students'])->name('students.index');
Route::get('/students/register',[UiDemoController::class,'registerStudent'])->name('students.create');
Route::get('/students/complete-profile',[UiDemoController::class,'completeProfile'])->name('students.complete-profile');
Route::get('/classes',[UiDemoController::class,'classes'])->name('classes.index');
Route::get('/subjects',[UiDemoController::class,'subjects'])->name('subjects.index');
