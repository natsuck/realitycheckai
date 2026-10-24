<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    use HasFactory;

    public const TYPE_MCQ = 'mcq';

    public const TYPE_IDENTIFICATION = 'identification';

    protected $fillable = [
        'quiz_id',
        'type',
        'question',
        'choices',
        'correct_answer',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'choices' => 'array',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'question_id');
    }

    public function isMcq(): bool
    {
        return $this->type === self::TYPE_MCQ;
    }
}
