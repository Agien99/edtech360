<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassStudentPosition extends Model
{
    protected $fillable = [
        'student_class_enrollment_id',
        'student_position_id',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(
            StudentClassEnrollment::class,
            'student_class_enrollment_id'
        );
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(
            StudentPosition::class,
            'student_position_id'
        );
    }
}