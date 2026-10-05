<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $classes = [
            ['name' => 'Form 6A', 'semester' => 'Semester 1', 'students' => 28, 'subjects' => 6, 'teacher' => 'Cikgu Aisyah'],
            ['name' => 'Form 6B', 'semester' => 'Semester 1', 'students' => 26, 'subjects' => 6, 'teacher' => 'Cikgu Farah'],
            ['name' => 'Form 6C', 'semester' => 'Semester 1', 'students' => 27, 'subjects' => 5, 'teacher' => 'Cikgu Daniel'],
            ['name' => 'STPM 1', 'semester' => 'Semester 3', 'students' => 22, 'subjects' => 7, 'teacher' => 'Cikgu Amir'],
        ];

        return view('classes.index', ['role' => $this->uiRole($request), 'classes' => $classes]);
    }
}
