<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuranAyah extends Model
{
    use HasFactory;

    protected $fillable = [
        'quran_surah_id',
        'number',
        'text_arabic',
        'text_indonesian',
        'text_english',
        'text_latin',
        'tafsir_indonesian',
        'tafsir_english',
        'audio_url',
        'keywords'
    ];

    protected $casts = [
        'keywords' => 'array',
    ];

    // Relationships
    public function surah()
    {
        return $this->belongsTo(QuranSurah::class, 'quran_surah_id');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

    // Scopes
    public function scopeBySurah($query, $surahId)
    {
        return $query->where('quran_surah_id', $surahId);
    }

    public function scopeByNumber($query, $number)
    {
        return $query->where('number', $number);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->whereFullText(['text_indonesian', 'tafsir_indonesian'], $searchTerm);
    }

    // Helper methods
    public function getFullReferenceAttribute()
    {
        return $this->surah->name_indonesian . ' (' . $this->surah->number . ':' . $this->number . ')';
    }
}
