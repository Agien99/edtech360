<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TeacherDelegationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherDelegationController extends Controller
{
    public function update(Request $request, User $teacher, TeacherDelegationService $service): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $service->setStudentEditing(
            $request->user(),
            $teacher,
            (bool) $validated['enabled']
        );

        return response()->json(['message' => 'Delegation updated.']);
    }
}
