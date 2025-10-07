<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AudioRelaxCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color_code',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    public function audioRelaxes()
    {
        return $this->hasMany(AudioRelax::class);
    }

    public function activeAudioRelaxes()
    {
        return $this->hasMany(AudioRelax::class)->where('is_active', true);
    }
}