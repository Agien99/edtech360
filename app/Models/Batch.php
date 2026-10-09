<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'intake_year',
        'start_date',
        'expected_end_date',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'intake_year' => 'integer',
            'start_date' => 'date',
            'expected_end_date' => 'date',
        ];
    }

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'batch_id');
    }

    
    public function studentMemberships(): HasMany
    {
        return $this->hasMany(StudentBatch::class, 'batch_id');
    }

}