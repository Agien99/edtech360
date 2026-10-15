<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\StudentBatch;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentRegistrationService
{
    /** @return array<int,array{student_number:null,full_name:string,email:string,temporary_password:string}> */
    public function registerRows(User $actor, int $sessionId, array $rows, string $method = 'form'): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $rows, $method): array {
            $session = AcademicSession::query()->with('batch')->findOrFail($sessionId);
            $batch = $session->batch;
            if (!in_array($session->status, ['planned', 'active'], true) || !$batch || $batch->status !== 'active') {
                throw ValidationException::withMessages(['academic_session_id' => 'Select an open intake with an active batch.']);
            }
            $credentials = [];
            foreach ($rows as $index => $row) {
                $email = mb_strtolower(trim($row['email']));
                $ic = preg_replace('/\D/', '', $row['ic_number']);
                if (User::withTrashed()->where('email', $email)->exists()) {
                    throw ValidationException::withMessages(["students.$index.email" => "Row ".($index + 1).": email already registered."]);
                }
                if (StudentProfile::withTrashed()->where('ic_number', $ic)->exists()) {
                    throw ValidationException::withMessages(["students.$index.ic_number" => "Row ".($index + 1).": IC already registered."]);
                }
                $password = Str::password(16, true, true, false, false);
                $user = User::create(['name' => trim($row['full_name']), 'email' => $email,
                    'password' => $password, 'is_active' => true]);
                $user->assignRole('student');
                $student = StudentProfile::create(['user_id' => $user->id,
                    'student_number' => null, 'ic_number' => $ic,
                    'full_name' => trim($row['full_name']), 'status' => 'active']);
                StudentBatch::create(['student_profile_id' => $student->id,
                    'batch_id' => $batch->id, 'joined_at' => now()->toDateString(), 'status' => 'active']);
                app(StudentAuditService::class)->record($actor, $student, 'register', [],
                    ['user_id' => $user->id, 'batch_id' => $batch->id, 'student_number' => null],
                    ['registration_method' => $method]);
                $credentials[] = ['student_number' => null, 'full_name' => $student->full_name,
                    'email' => $email, 'temporary_password' => $password];
            }
            return $credentials;
        }, 3);
    }
}
