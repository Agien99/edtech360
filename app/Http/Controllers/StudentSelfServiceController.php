<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Services\StudentAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StudentSelfServiceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $student = StudentProfile::query()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        Gate::forUser($request->user())
            ->authorize('viewOwn', $student);

        return response()->json([
            'student_number' => $student->student_number,
            'full_name' => $student->full_name,
            'phone' => $student->phone,
        ]);
    }

    public function update(
        Request $request,
        StudentAuditService $audit
    ): JsonResponse {
        $validated = $request->validate([
            'phone' => ['present', 'nullable', 'string', 'max:30'],
        ]);

        $student = DB::transaction(function () use (
            $request,
            $validated,
            $audit
        ): StudentProfile {
            $student = StudentProfile::query()
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($request->user())
                ->authorize('updateOwn', $student);

            $oldPhone = $student->phone;
            $student->phone = $validated['phone'];

            if ($student->isDirty('phone')) {
                $student->save();

                $audit->record(
                    $request->user(),
                    $student,
                    'self_update',
                    ['phone' => $oldPhone],
                    ['phone' => $student->phone],
                    ['changed_fields' => ['phone']]
                );
            }

            return $student;
        }, 3);

        return response()->json([
            'message' => 'Your phone number has been updated.',
            'student' => [
                'student_number' => $student->student_number,
                'full_name' => $student->full_name,
                'phone' => $student->phone,
            ],
        ]);
    }
}