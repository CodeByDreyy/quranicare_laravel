<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudioCategory extends Model
{
    use HasFactory;

    protected $table = 'audio_categories';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the audio for the category.
     */
    public function audioRelax(): HasMany
    {
        return $this->hasMany(AudioRelax::class, 'audio_category_id');
    }

    /**
     * Get only active audio for the category.
     */
    public function activeAudioRelax(): HasMany
    {
        return $this->hasMany(AudioRelax::class, 'audio_category_id')
                    ->where('is_active', true);
    }

    /**
     * Scope to get only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
