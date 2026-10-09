<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherSubjectClass extends Model
{
    protected $table = 'teacher_subject_class';

    protected $fillable = [
        'teacher_profile_id',
        'subject_id',
        'academic_session_id',
        'class_id',
        'semester_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
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

    public function teachingMaterials(): HasMany
    {
        return $this->hasMany(
            TeachingMaterial::class,
            'teacher_subject_class_id'
        );
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(
            Assignment::class,
            'teacher_subject_class_id'
        );
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(
            Quiz::class,
            'teacher_subject_class_id'
        );
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(
            TimetableEntry::class,
            'teacher_subject_class_id'
        );
    }

}