<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AssignmentSubmission extends Model
{
    protected $fillable = [
        'assignment_id',
        'student_class_enrollment_id',
        'attempt_number',
        'submission_text',
        'submitted_at',
        'status',
        'marks_awarded',
        'teacher_feedback',
        'graded_by',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'submitted_at' => 'datetime',
            'marks_awarded' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(
            StudentClassEnrollment::class,
            'student_class_enrollment_id'
        );
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(
            FileAttachment::class,
            'attachable'
        );
    }
    
}