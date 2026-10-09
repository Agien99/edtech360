<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_version_id',
        'question_text',
        'question_type',
        'marks',
        'sort_order',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'marks' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(QuizVersion::class, 'quiz_version_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class, 'quiz_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'quiz_question_id');
    }
}