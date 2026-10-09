<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableEntry extends Model
{
    protected $fillable = [
        'timetable_id',
        'teacher_subject_class_id',
        'class_id',
        'semester_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(
            Timetable::class,
            'timetable_id'
        );
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(
            TeacherSubjectClass::class,
            'teacher_subject_class_id'
        );
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(
            SchoolClass::class,
            'class_id'
        );
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(
            Semester::class,
            'semester_id'
        );
    }
}