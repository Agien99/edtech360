<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Services\StudentAuditService;
use App\Services\StudentTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentManagementController extends Controller
{
    public function update(
        Request $request,
        StudentProfile $student,
        StudentAuditService $audit
    ): RedirectResponse {
        $this->authorizeStudent($request, 'update', $student);

        // Only these fields may be edited through this endpoint.
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use (
            $request,
            $student,
            $validated,
            $audit
        ) {
            $lockedStudent = StudentProfile::query()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Re-authorize after acquiring the lock.
            $this->authorizeStudent(
                $request,
                'update',
                $lockedStudent
            );

            $oldValues = $lockedStudent->only([
                'full_name',
                'phone',
            ]);

            $lockedStudent->fill($validated);

            if (! $lockedStudent->isDirty()) {
                return;
            }

            $lockedStudent->save();

            $audit->record(
                $request->user(),
                $lockedStudent,
                'update',
                $oldValues,
                $lockedStudent->only([
                    'full_name',
                    'phone',
                ]),
                ['changed_fields' => array_keys(
                    $lockedStudent->getChanges()
                )]
            );
        }, 3);

        return back()->with(
            'success',
            'Student information updated.'
        );
    }

    public function transfer(
        Request $request,
        StudentProfile $student,
        StudentTransferService $service
    ): RedirectResponse {
        $this->authorizeStudent($request, 'transfer', $student);

        $validated = $request->validate([
            'class_id' => [
                'required',
                'integer',
                Rule::exists('classes', 'id')
                    ->whereNull('deleted_at'),
            ],
        ]);

        $service->transfer(
            $request->user(),
            $student,
            (int) $validated['class_id']
        );

        return back()->with(
            'success',
            'Student transfer completed.'
        );
    }

    private function authorizeStudent(
        Request $request,
        string $ability,
        StudentProfile $student
    ): void {
        \Illuminate\Support\Facades\Gate::forUser(
            $request->user()
        )->authorize($ability, $student);
    }
}