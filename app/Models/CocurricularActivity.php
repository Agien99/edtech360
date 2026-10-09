<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CocurricularActivity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function advisors(): HasMany
    {
        return $this->hasMany(
            CocurricularAdvisor::class,
            'cocurricular_activity_id'
        );
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(
            CocurricularMembership::class,
            'cocurricular_activity_id'
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            CocurricularEvent::class,
            'cocurricular_activity_id'
        );
    }
}