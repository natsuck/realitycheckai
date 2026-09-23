<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResult extends Model
{
    protected $fillable = [
        'user_id',
        'program_code',
        'program_name',
        'confidence',
        'fit_level',
        'source',
        'strongest_trait',
        'strongest_trait_percentage',
        'response_flags',
        'completion_seconds',
        'result',
        'category_summary',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'strongest_trait_percentage' => 'decimal:2',
            'response_flags' => 'array',
            'result' => 'array',
            'category_summary' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function hasResponseFlags(): bool
    {
        return ! empty($this->response_flags);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
