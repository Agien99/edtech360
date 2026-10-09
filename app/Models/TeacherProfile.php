<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherProfile extends Model
{
    use SoftDeletes;

    protected $table = 'teacher_profiles';

    protected $fillable = [
        'user_id',
        'staff_number',
        'full_name',
        'phone',
        'joined_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    
    public function classAssignments(): HasMany
    {
        return $this->hasMany(TeacherClassAssignment::class, 'teacher_profile_id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectClass::class, 'teacher_profile_id');
    }

    public function subjectGroupMemberships(): HasMany
    {
        return $this->hasMany(SubjectGroupMember::class, 'teacher_profile_id');
    }

    public function cocurricularAdvisorships(): HasMany
    {
        return $this->hasMany(
            CocurricularAdvisor::class,
            'teacher_profile_id'
        );
    }

}