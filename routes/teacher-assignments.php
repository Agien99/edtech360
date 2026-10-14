<?php
use App\Http\Controllers\TeacherAssignmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','account.active'])->prefix('teacher-assignments')->name('teacher-assignments.')->group(function () {
    Route::get('/',[TeacherAssignmentController::class,'index'])->name('index');
    Route::post('/class',[TeacherAssignmentController::class,'storeClass'])->name('class.store');
    Route::post('/subject',[TeacherAssignmentController::class,'storeSubject'])->name('subject.store');
    Route::post('/group',[TeacherAssignmentController::class,'storeGroup'])->name('group.store');
    Route::patch('/{kind}/{id}/end',[TeacherAssignmentController::class,'end'])
        ->whereIn('kind',['class','subject','group'])->whereNumber('id')->name('end');
});
