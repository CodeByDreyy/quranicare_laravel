<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuranSurah extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name_arabic',
        'name_indonesian',
        'name_english',
        'name_latin',
        'number_of_ayahs',
        'place',
        'description_indonesian',
        'description_english',
        'audio_url'
    ];

    // Relationships
    public function ayahs()
    {
        return $this->hasMany(QuranAyah::class);
    }

    // Scopes
    public function scopeByPlace($query, $place)
    {
        return $query->where('place', $place);
    }

    public function scopeByNumber($query, $number)
    {
        return $query->where('number', $number);
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return $this->number . '. ' . $this->name_indonesian . ' (' . $this->name_arabic . ')';
    }
}
