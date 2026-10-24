<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public const CURRENT_INSTRUMENT_VERSION = 'aguhon-150-v1';

    protected $fillable = [
        'question_text',
        'category',
        'is_reverse',
        'instrument_version',
        'item_number',
        'section',
        'question_type',
        'riasec_type',
        'choices',
        'correct_answer',
    ];

    protected function casts(): array
    {
        return [
            'is_reverse' => 'boolean',
            'choices' => 'array',
            'correct_answer' => 'integer',
            'item_number' => 'integer',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
