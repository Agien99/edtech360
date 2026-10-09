<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use SoftDeletes;

    protected $table = 'student_profiles';

    protected $fillable = [
        'user_id',
        'student_number',
        'full_name',
        'date_of_birth',
        'phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    
    public function batchMemberships(): HasMany
    {
        return $this->hasMany(StudentBatch::class, 'student_profile_id');
    }

    public function classEnrollments(): HasMany
    {
        return $this->hasMany(StudentClassEnrollment::class, 'student_profile_id');
    }

    public function cocurricularMemberships(): HasMany
    {
        return $this->hasMany(
            CocurricularMembership::class,
            'student_profile_id'
        );
    }

}