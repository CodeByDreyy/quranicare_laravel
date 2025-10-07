<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'birth_date',
        'gender',
        'phone',
        'profile_picture',
        'bio',
        'preferred_language',
        'is_active',
        'last_login_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function moods()
    {
        return $this->hasMany(Mood::class);
    }

    public function moodStatistics()
    {
        return $this->hasMany(MoodStatistic::class);
    }

    public function breathingSessions()
    {
        return $this->hasMany(BreathingSession::class);
    }

    public function audioListeningSessions()
    {
        return $this->hasMany(AudioListeningSession::class);
    }

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

    public function dzikirSessions()
    {
        return $this->hasMany(UserDzikirSession::class);
    }

    public function qalbuConversations()
    {
        return $this->hasMany(QalbuConversation::class);
    }

    public function materialProgress()
    {
        return $this->hasMany(UserMaterialProgress::class);
    }

    public function favorites()
    {
        return $this->hasMany(UserFavorite::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
