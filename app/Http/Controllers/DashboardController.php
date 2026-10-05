<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $role = $this->uiRole($request);

        $stats = match ($role) {
            'teacher', 'class-teacher' => [
                ['label' => 'My Classes', 'value' => '4', 'meta' => '2 today', 'icon' => 'bi-people', 'tone' => 'blue'],
                ['label' => 'My Subjects', 'value' => '6', 'meta' => 'Active subjects', 'icon' => 'bi-journal-bookmark', 'tone' => 'purple'],
                ['label' => 'Students', 'value' => '116', 'meta' => 'Across my classes', 'icon' => 'bi-mortarboard', 'tone' => 'cyan'],
                ['label' => 'Attendance Today', 'value' => '95.7%', 'meta' => '↑ 1.8% this week', 'icon' => 'bi-check2-circle', 'tone' => 'green'],
            ],
            'student' => [
                ['label' => 'My Subjects', 'value' => '6', 'meta' => 'This semester', 'icon' => 'bi-journal-bookmark', 'tone' => 'blue'],
                ['label' => 'Homework', 'value' => '3', 'meta' => 'Pending', 'icon' => 'bi-journal-text', 'tone' => 'orange'],
                ['label' => 'Quiz', 'value' => '2', 'meta' => 'Available', 'icon' => 'bi-patch-question', 'tone' => 'purple'],
                ['label' => 'Attendance', 'value' => '94.8%', 'meta' => 'This semester', 'icon' => 'bi-check2-circle', 'tone' => 'green'],
            ],
            default => [
                ['label' => 'Total Students', 'value' => '328', 'meta' => '↑ 12 this semester', 'icon' => 'bi-people', 'tone' => 'blue'],
                ['label' => 'Total Teachers', 'value' => '24', 'meta' => '↑ 2 this semester', 'icon' => 'bi-person-workspace', 'tone' => 'purple'],
                ['label' => 'Total Classes', 'value' => '12', 'meta' => 'Form 6 · 3 semesters', 'icon' => 'bi-easel2', 'tone' => 'cyan'],
                ['label' => 'Total Subjects', 'value' => '18', 'meta' => 'Active subjects', 'icon' => 'bi-journal-bookmark', 'tone' => 'green'],
            ],
        };

        return view('dashboard.index', [
            'role' => $role,
            'roleLabel' => $this->roleLabel($role),
            'stats' => $stats,
        ]);
    }
}
