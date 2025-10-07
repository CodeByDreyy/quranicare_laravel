<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UnansweredQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'qalbu_conversation_id',
        'question',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}


