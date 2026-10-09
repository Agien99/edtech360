<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CocurricularAdvisor extends Model
{
    protected $fillable = [
        'cocurricular_activity_id',
        'teacher_profile_id',
        'academic_session_id',
        'position',
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

    public function activity(): BelongsTo
    {
        return $this->belongsTo(
            CocurricularActivity::class,
            'cocurricular_activity_id'
        );
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(
            TeacherProfile::class,
            'teacher_profile_id'
        );
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(
            AcademicSession::class,
            'academic_session_id'
        );
    }
}