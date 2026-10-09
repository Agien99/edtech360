<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentClassEnrollment extends Model
{
    protected $fillable = [
        'student_profile_id',
        'academic_session_id',
        'class_id',
        'semester_id',
        'enrolled_at',
        'ended_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(
            AssignmentSubmission::class,
            'student_class_enrollment_id'
        );
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(
            QuizAttempt::class,
            'student_class_enrollment_id'
        );
    }

    public function studentPositions(): HasMany
    {
        return $this->hasMany(
            ClassStudentPosition::class,
            'student_class_enrollment_id'
        );
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(
            AttendanceRecord::class,
            'student_class_enrollment_id'
        );
    }

}