<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CocurricularMembership extends Model
{
    protected $fillable = [
        'cocurricular_activity_id',
        'student_profile_id',
        'academic_session_id',
        'position',
        'joined_at',
        'left_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'left_at' => 'date',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(
            CocurricularActivity::class,
            'cocurricular_activity_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            StudentProfile::class,
            'student_profile_id'
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