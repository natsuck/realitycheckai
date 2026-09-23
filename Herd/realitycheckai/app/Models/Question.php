<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'question_text',
        'category',
        'is_reverse',
    ];

    protected function casts(): array
    {
        return [
            'is_reverse' => 'boolean',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
