<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicSession extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'start_date', 'end_date', 'is_current', 'status'];
    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_current' => 'boolean'];
    }
    public function batch(): HasOne { return $this->hasOne(Batch::class, 'academic_session_id'); }
    public function semesters(): HasMany { return $this->hasMany(Semester::class, 'academic_session_id'); }
    public function schoolClasses(): HasMany { return $this->hasMany(SchoolClass::class, 'academic_session_id'); }
    public function cocurricularAdvisors(): HasMany { return $this->hasMany(CocurricularAdvisor::class, 'academic_session_id'); }
    public function cocurricularMemberships(): HasMany { return $this->hasMany(CocurricularMembership::class, 'academic_session_id'); }
    public function cocurricularEvents(): HasMany { return $this->hasMany(CocurricularEvent::class, 'academic_session_id'); }
}
