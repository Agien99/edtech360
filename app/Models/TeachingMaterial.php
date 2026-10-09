<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TeachingMaterial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_subject_class_id',
        'created_by',
        'title',
        'description',
        'content',
        'material_type',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
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

    public function attachments(): MorphMany
    {
        return $this->morphMany(FileAttachment::class, 'attachable');
    }

}