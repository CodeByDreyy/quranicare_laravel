<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QalbuConversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'conversation_type',
        'user_emotion',
        'context_data',
        'is_active',
        'last_message_at',
    ];
}
