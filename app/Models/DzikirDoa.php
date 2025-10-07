<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DzikirDoa extends Model
{
    use HasFactory;

    protected $table = 'dzikir_doa';

    protected $fillable = [
        'dzikir_category_id',
        'title',
        'arabic_text',
        'latin_text',
        'indonesian_translation',
        'benefits',
        'context',
        'source',
        'audio_path',
        'repeat_count',
        'emotional_tags',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'repeat_count' => 'integer',
        'emotional_tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the category that owns the dzikir doa.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DzikirCategory::class, 'dzikir_category_id');
    }

    /**
     * Scope to get only active dzikir doa
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only featured dzikir doa
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('dzikir_category_id', $categoryId);
    }
}
