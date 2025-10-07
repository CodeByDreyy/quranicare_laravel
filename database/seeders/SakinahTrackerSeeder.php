<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\QuranReadingSession;
use App\Models\UserDoaDzikirSession;
use Carbon\Carbon;

class SakinahTrackerSeeder extends Seeder
{
    public function run()
    {
        // Get first user for testing (or create one)
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Generate sample activities for the last 30 days
        $activities = [
            UserActivityLog::TYPE_QURAN_READING,
            UserActivityLog::TYPE_DZIKIR_SESSION,
            UserActivityLog::TYPE_BREATHING_EXERCISE,
            UserActivityLog::TYPE_AUDIO_RELAXATION,
            UserActivityLog::TYPE_JOURNAL_WRITING,
            UserActivityLog::TYPE_QALBU_CHAT,
            UserActivityLog::TYPE_PSYCHOLOGY_MATERIAL,
            UserActivityLog::TYPE_MOOD_TRACKING
        ];

        $now = Carbon::now();
        
        for ($i = 0; $i < 30; $i++) {
            $date = $now->copy()->subDays($i);
            
            // Random number of activities per day (0-5)
            $dailyActivities = rand(0, 5);
            
            for ($j = 0; $j < $dailyActivities; $j++) {
                $activityType = $activities[array_rand($activities)];
                $activityData = $this->generateActivityData($activityType);
                $randomTime = $date->copy()->addMinutes(rand(0, 1440));
                
                UserActivityLog::logActivity($user->id, $activityType, [
                    'title' => $activityData['title'] ?? null,
                    'duration' => $activityData['duration'] ?? rand(300, 1800),
                    'completion' => rand(50, 100),
                    'metadata' => $activityData,
                    'date' => $date->toDateString(),
                    'time' => $randomTime->format('H:i:s'),
                ]);
            }
        }

        // Note: Skipping QuranReadingSession creation for now due to structure difference
        // The structure expects quran_surah_id from quran_surahs table which may not exist yet

        echo "✅ Sample Sakinah Tracker data created for user: {$user->email}\n";
    }

    private function generateActivityData($activityType)
    {
        switch ($activityType) {
            case UserActivityLog::TYPE_QURAN_READING:
                $surahs = ['Al-Fatihah', 'Al-Baqarah', 'Ali-Imran', 'An-Nisa'];
                return [
                    'title' => $surahs[rand(0, 3)],
                    'surah_name' => $surahs[rand(0, 3)],
                    'surah_number' => rand(1, 4),
                    'reading_type' => ['full_surah', 'ayah_range', 'tilawah'][rand(0, 2)]
                ];
                
            case UserActivityLog::TYPE_DZIKIR_SESSION:
                $dzikirs = ['Istighfar', 'Tasbih', 'Tahmid', 'Takbir'];
                return [
                    'title' => $dzikirs[rand(0, 3)],
                    'dzikir_name' => $dzikirs[rand(0, 3)],
                    'target_count' => [33, 99, 100][rand(0, 2)]
                ];
                
            case UserActivityLog::TYPE_BREATHING_EXERCISE:
                $exercises = ['4-7-8 Breathing', 'Box Breathing', 'Deep Breathing'];
                return [
                    'title' => $exercises[rand(0, 2)],
                    'exercise_type' => ['4-7-8', 'box_breathing', 'deep_breathing'][rand(0, 2)]
                ];
                
            case UserActivityLog::TYPE_JOURNAL_WRITING:
                return [
                    'title' => 'Jurnal Refleksi Harian',
                    'mood' => ['senang', 'sedih', 'biasa_saja', 'bersyukur'][rand(0, 3)],
                    'word_count' => rand(50, 300)
                ];
                
            case UserActivityLog::TYPE_QALBU_CHAT:
                return [
                    'title' => 'QalbuChat Session',
                    'messages_count' => rand(5, 20)
                ];
                
            case UserActivityLog::TYPE_PSYCHOLOGY_MATERIAL:
                $materials = ['Stress Management', 'Anxiety Relief', 'Islamic Psychology', 'Mindfulness'];
                return [
                    'title' => $materials[rand(0, 3)]
                ];
                
            case UserActivityLog::TYPE_AUDIO_RELAXATION:
                $audios = ['Rain Sounds', 'Quran Recitation', 'Nature Sounds', 'White Noise'];
                return [
                    'title' => $audios[rand(0, 3)]
                ];
                
            case UserActivityLog::TYPE_MOOD_TRACKING:
                return [
                    'title' => 'Mood Check-in',
                    'mood' => ['senang', 'sedih', 'biasa_saja', 'bersyukur'][rand(0, 3)]
                ];
                
            default:
                return [
                    'title' => 'General Activity'
                ];
        }
    }
}