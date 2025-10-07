<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoaDzikir extends Model
{
    use HasFactory;

    protected $table = 'doa_dzikir';

    protected $fillable = [
        'grup',
        'nama',
        'ar',
        'tr',
        'idn',
        'tentang',
        'tag',
        'api_id',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'tag' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user sessions for this doa/dzikir.
     */
    public function userSessions(): HasMany
    {
        return $this->hasMany(UserDoaDzikirSession::class, 'doa_dzikir_id');
    }

    /**
     * Scope to get only active doa/dzikir.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only featured doa/dzikir.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    /**
     * Scope to filter by group.
     */
    public function scopeByGrup($query, $grup)
    {
        return $query->where('grup', $grup);
    }

    /**
     * Scope to filter by tags.
     */
    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tag', $tag);
    }

    /**
     * Scope to search in text fields.
     */
    public function scopeSearch($query, $search)
    {
        return $query->whereFullText(['nama', 'ar', 'tr', 'idn', 'tentang'], $search)
                    ->orWhere('nama', 'LIKE', "%{$search}%")
                    ->orWhere('tr', 'LIKE', "%{$search}%")
                    ->orWhere('idn', 'LIKE', "%{$search}%");
    }

    /**
     * Get unique groups for filtering.
     */
    public static function getGroups()
    {
        return static::active()
            ->distinct()
            ->pluck('grup')
            ->sort()
            ->values();
    }

    /**
     * Get unique tags for filtering.
     */
    public static function getTags()
    {
        return static::active()
            ->get()
            ->pluck('tag')
            ->flatten()
            ->unique()
            ->sort()
            ->values();
    }
}