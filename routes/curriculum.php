<?php
use App\Http\Controllers\CurriculumManagementController as C;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','account.active'])->prefix('curriculum')->name('curriculum.')->group(function(){
 Route::get('/',[C::class,'index'])->name('index');
 Route::post('/groups',[C::class,'group'])->name('groups.store');
 Route::post('/subjects',[C::class,'subject'])->name('subjects.store');
 Route::patch('/status/{type}/{id}',[C::class,'status'])->name('status');
 Route::put('/classes/{schoolClass}/subjects',[C::class,'combination'])->name('combination');
 Route::post('/coordinators',[C::class,'assignCoordinator'])->name('coordinators');
 Route::post('/syllabuses',[C::class,'syllabus'])->name('syllabuses');
 Route::post('/syllabus-links',[C::class,'linkSyllabus'])->name('links');
 Route::post('/topics',[C::class,'topic'])->name('topics');
 Route::patch('/topics/{id}',[C::class,'editTopic'])->name('topics.update');
 Route::post('/topics/{id}/publish',[C::class,'publish'])->name('topics.publish');
});
