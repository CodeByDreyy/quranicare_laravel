<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DzikirCategory extends Model
{
    use HasFactory;

    protected $table = 'dzikir_categories';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the dzikir doa for the category.
     */
    public function dzikirDoa(): HasMany
    {
        return $this->hasMany(DzikirDoa::class, 'dzikir_category_id');
    }

    /**
     * Get only active dzikir doa for the category.
     */
    public function activeDzikirDoa(): HasMany
    {
        return $this->hasMany(DzikirDoa::class, 'dzikir_category_id')
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
