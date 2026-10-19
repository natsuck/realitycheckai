<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'location',
        'institution_type',
        'website',
    ];

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class)->withTimestamps();
    }
}
