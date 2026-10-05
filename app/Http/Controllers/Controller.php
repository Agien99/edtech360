<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function uiRole(Request $request): string
    {
        $role = (string) $request->query('role', 'administrator');

        return in_array($role, ['administrator', 'teacher', 'class-teacher', 'student'], true)
            ? $role
            : 'administrator';
    }

    protected function roleLabel(string $role): string
    {
        return match ($role) {
            'teacher' => 'Teacher',
            'class-teacher' => 'Class Teacher',
            'student' => 'Student',
            default => 'Administrator',
        };
    }
}
