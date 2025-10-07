<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PsychologyMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'summary',
        'category',
        'tags',
        'author',
        'reading_time',
        'is_published',
        'psychology_category_id'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'tags' => 'array'
    ];

    public function category()
    {
        return $this->belongsTo(PsychologyCategory::class, 'psychology_category_id');
    }
}
