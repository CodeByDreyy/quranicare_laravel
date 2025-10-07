<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PsychologyCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color'
    ];

    public function psychologyMaterials()
    {
        return $this->hasMany(PsychologyMaterial::class, 'psychology_category_id');
    }
}
