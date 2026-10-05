<?php
namespace App\Http\Controllers;
use Illuminate\Contracts\View\View;
class UiDemoController {
 public function login(): View { return view('auth.login'); }
 public function dashboard(): View { return view('dashboard'); }
 public function students(): View { return view('students.index'); }
 public function registerStudent(): View { return view('students.create'); }
 public function completeProfile(): View { return view('students.complete-profile'); }
 public function classes(): View { return view('classes.index'); }
 public function subjects(): View { return view('subjects.index'); }
}
