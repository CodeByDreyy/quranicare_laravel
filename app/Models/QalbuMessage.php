<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QalbuMessage extends Model
{
    protected $fillable = [
        'qalbu_conversation_id',
        'sender',
        'message',
        'ai_sources',
        'suggested_actions',
        'ai_response_type',
        'is_helpful',
        'user_feedback',
    ];
}
