<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ScopedClassController;
use App\Http\Controllers\SchoolClassManagementController;
use App\Http\Controllers\ScopedStudentController;
use App\Http\Controllers\StudentManagementController;
use App\Http\Controllers\StudentSelfServiceController;
use App\Http\Controllers\TeacherDelegationController;
use App\Http\Controllers\UiDemoController;
use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\AcademicSessionLifecycleController;
use App\Http\Controllers\BatchController;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class,'showLogin'])->name('login');
    Route::post('/login', [AuthController::class,'login'])->middleware('throttle:5,1')->name('login.store');
});
Route::middleware(['auth','account.active'])->group(function () {
    Route::post('/logout', [AuthController::class,'logout'])->name('logout');
    Route::get('/dashboard', [UiDemoController::class,'dashboard'])->middleware('can:dashboard.view')->name('dashboard');
    Route::get('/students', [ScopedStudentController::class,'index'])->middleware('can:students.view')->name('students.index');
    Route::get('/students/register', [UiDemoController::class,'registerStudent'])->middleware('can:students.create')->name('students.create');
    Route::get('/students/complete-profile', [UiDemoController::class,'completeProfile'])->middleware('can:students.update')->name('students.complete-profile');
    Route::get('/classes', [ScopedClassController::class,'index'])->middleware('can:classes.view')->name('classes.index');
    Route::post('/classes', [SchoolClassManagementController::class,'store'])->middleware('can:classes.create')->name('classes.store');
    Route::patch('/classes/{schoolClass}', [SchoolClassManagementController::class,'update'])->middleware('can:classes.update')->name('classes.update');
    Route::patch('/classes/{schoolClass}/deactivate', [SchoolClassManagementController::class,'deactivate'])->middleware('can:classes.update')->name('classes.deactivate');
    Route::get('/subjects', [UiDemoController::class,'subjects'])->middleware('can:subjects.view')->name('subjects.index');
    Route::patch('/students/{student}/profile', [StudentManagementController::class,'update'])->name('students.profile.update');
    Route::post('/students/{student}/transfer', [StudentManagementController::class,'transfer'])->name('students.transfer');
    Route::get('/my/student-profile', [StudentSelfServiceController::class,'show'])->name('student.self.show');
    Route::patch('/my/student-profile', [StudentSelfServiceController::class,'update'])->name('student.self.update');
    Route::patch('/teachers/{teacher}/delegations/student-editing', [TeacherDelegationController::class,'update'])->name('teachers.delegations.student-editing');

    Route::get('/academic-sessions', [AcademicSessionController::class,'index'])
        ->middleware('can:academic_sessions.view')->name('academic-sessions.index');
    Route::post('/academic-sessions', [AcademicSessionController::class,'store'])
        ->middleware('can:academic_sessions.manage')->name('academic-sessions.store');
    Route::prefix('academic-sessions/{academicSession}/semesters')->name('academic-sessions.semesters.')
        ->group(function () {
            Route::get('/', [SemesterController::class,'index'])
                ->middleware('can:academic_sessions.view')->name('index');
            Route::post('/', [SemesterController::class,'store'])
                ->middleware('can:academic_sessions.manage')->name('store');
            Route::patch('/{semester}', [SemesterController::class,'update'])
                ->middleware('can:academic_sessions.manage')->name('update');
        });
    Route::prefix('academic-sessions/{academicSession}')->name('academic-sessions.')
        ->middleware('can:academic_sessions.manage')->group(function () {
            Route::post('/activate', [AcademicSessionLifecycleController::class,'activate'])->name('activate');
            Route::post('/advance-semester', [AcademicSessionLifecycleController::class,'advanceSemester'])->name('advance-semester');
            Route::post('/close', [AcademicSessionLifecycleController::class,'close'])->name('close');
        });
    Route::get('/batches', [BatchController::class,'index'])
        ->middleware('can:batches.view')->name('batches.index');
    // Batch creation, editing and lifecycle changes are now derived from Academic Sessions.
});
