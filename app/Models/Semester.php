<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'academic_session_id',
        'number',
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function studentClassEnrollments(): HasMany
    {
        return $this->hasMany(
            StudentClassEnrollment::class,
            'semester_id'
        );
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(
            TeacherSubjectClass::class,
            'semester_id'
        );
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(
            Timetable::class,
            'semester_id'
        );
    }
}