<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        return view('students.index', [
            'role' => $this->uiRole($request),
            'students' => [
                ['name' => 'Ahmad Firdaus', 'class' => '6A', 'id' => '060101-14-1234', 'gender' => 'Male', 'status' => 'Active'],
                ['name' => 'Siti Nur Aisyah', 'class' => '6B', 'id' => '060218-14-5678', 'gender' => 'Female', 'status' => 'Active'],
                ['name' => 'Tan Wei Jie', 'class' => '6A', 'id' => '060305-14-0912', 'gender' => 'Male', 'status' => 'Active'],
                ['name' => 'Nurul Izzah', 'class' => '6C', 'id' => '060415-14-3456', 'gender' => 'Female', 'status' => 'Inactive'],
                ['name' => 'Muhammad Hakim', 'class' => '6B', 'id' => '060522-14-7890', 'gender' => 'Male', 'status' => 'Active'],
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('students.create', ['role' => $this->uiRole($request)]);
    }
}
