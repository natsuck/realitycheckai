<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Resource extends Model
{
    use HasFactory;

    public const TYPE_CET = 'cet';

    public const TYPE_SCHOLARSHIP = 'scholarship';

    public const TYPE_UNIVERSITY = 'university';

    public const TYPE_STRAND = 'strand';

    public const TYPES = [
        self::TYPE_CET => 'College Entrance Exam (CET)',
        self::TYPE_SCHOLARSHIP => 'Scholarship / Grant',
        self::TYPE_UNIVERSITY => 'University',
        self::TYPE_STRAND => 'SHS Strand',
    ];

    protected $fillable = [
        'type',
        'title',
        'slug',
        'summary',
        'link',
        'is_published',
        'sort_order',
        'period',
        'coverage',
        'tips',
        'grantor',
        'criteria',
        'opens_at',
        'closes_at',
        'short_name',
        'location',
        'institution_type',
        'top_programs',
        'entrance_exam',
        'tuition_range',
        'website',
        'logo_bg',
        'emoji',
        'subtitle',
        'alignments',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'opens_at' => 'date',
            'closes_at' => 'date',
            'top_programs' => 'array',
        ];
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class)->withTimestamps();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type] ?? $type;
    }
}
