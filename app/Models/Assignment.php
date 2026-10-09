<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Assignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_subject_class_id',
        'created_by',
        'title',
        'instructions',
        'total_marks',
        'available_at',
        'due_at',
        'published_at',
        'allow_late_submission',
        'allow_file_submission',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_marks' => 'decimal:2',
            'available_at' => 'datetime',
            'due_at' => 'datetime',
            'published_at' => 'datetime',
            'allow_late_submission' => 'boolean',
            'allow_file_submission' => 'boolean',
        ];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(
            TeacherSubjectClass::class,
            'teacher_subject_class_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FileAttachment::class, 'attachable');
    }

}