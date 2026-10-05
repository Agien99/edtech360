<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MAT601', 'teachers' => 3, 'classes' => 4, 'students' => 116, 'tone' => 'purple'],
            ['name' => 'Chemistry', 'code' => 'CHE601', 'teachers' => 2, 'classes' => 4, 'students' => 113, 'tone' => 'cyan'],
            ['name' => 'Physics', 'code' => 'PHY601', 'teachers' => 2, 'classes' => 3, 'students' => 89, 'tone' => 'orange'],
            ['name' => 'Biology', 'code' => 'BIO601', 'teachers' => 2, 'classes' => 3, 'students' => 92, 'tone' => 'green'],
            ['name' => 'Sejarah', 'code' => 'SEJ601', 'teachers' => 2, 'classes' => 4, 'students' => 108, 'tone' => 'red'],
            ['name' => 'Bahasa Melayu', 'code' => 'BM601', 'teachers' => 2, 'classes' => 4, 'students' => 116, 'tone' => 'blue'],
        ];

        return view('subjects.index', ['role' => $this->uiRole($request), 'subjects' => $subjects]);
    }
}
