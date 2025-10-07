<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Create a test user if doesn't exist
        $userId = DB::table('users')->first()?->id ?? 1;
        
        $notifications = [
            [
                'user_id' => $userId,
                'title' => 'Selamat Datang di QuraniCare',
                'message' => 'Terima kasih telah bergabung dengan QuraniCare. Mari mulai perjalanan spiritual Anda bersama kami.',
                'type' => 'new_content',
                'data' => json_encode(['category' => 'welcome']),
                'is_read' => false,
                'scheduled_at' => $now,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userId,
                'title' => 'Jangan Lupa Dzikir Pagi',
                'message' => 'Mulai hari Anda dengan dzikir pagi untuk ketenangan hati. Klik untuk melihat dzikir yang direkomendasikan.',
                'type' => 'dzikir_reminder',
                'data' => json_encode(['dzikir_type' => 'morning']),
                'is_read' => false,
                'scheduled_at' => $now->copy()->addHour(),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userId,
                'title' => 'Update Mood Harian',
                'message' => 'Bagaimana perasaan Anda hari ini? Catat mood Anda untuk membantu tracking kesehatan mental.',
                'type' => 'mood_reminder',
                'data' => json_encode(['reminder_type' => 'daily_mood']),
                'is_read' => false,
                'scheduled_at' => $now->copy()->addHours(2),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userId,
                'title' => 'Fitur Baru: Audio Relaksasi',
                'message' => 'Nikmati koleksi audio relaksasi baru untuk menenangkan pikiran dan jiwa Anda.',
                'type' => 'new_content',
                'data' => json_encode(['content_type' => 'audio_relax']),
                'is_read' => false,
                'scheduled_at' => $now->copy()->addDay(),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userId,
                'title' => 'Waktu Istirahat Mental',
                'message' => 'Sudah waktunya untuk istirahat. Coba latihan pernapasan 5 menit untuk menenangkan diri.',
                'type' => 'breathing_reminder',
                'data' => json_encode(['duration' => 5]),
                'is_read' => false,
                'scheduled_at' => $now->copy()->addHours(4),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userId,
                'title' => 'Refleksi Ayat Harian',
                'message' => 'Renungkan ayat Al-Quran pilihan hari ini untuk mendapatkan ketenangan dan hikmah.',
                'type' => 'new_content',
                'data' => json_encode(['content_type' => 'quran_verse']),
                'is_read' => false,
                'scheduled_at' => $now->copy()->addDays(2),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userId,
                'title' => 'Tips Kesehatan Mental',
                'message' => 'Baca artikel terbaru tentang cara menjaga kesehatan mental dari perspektif Islam.',
                'type' => 'new_content',
                'data' => json_encode(['content_type' => 'article']),
                'is_read' => false,
                'scheduled_at' => $now->copy()->addDays(3),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userId,
                'title' => 'Pencapaian Mingguan',
                'message' => 'Selamat! Anda telah menyelesaikan aktivitas spiritual selama 7 hari berturut-turut.',
                'type' => 'achievement',
                'data' => json_encode(['achievement_type' => 'weekly_streak', 'streak_count' => 7]),
                'is_read' => false,
                'scheduled_at' => $now->copy()->addWeek(),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('notifications')->insert($notifications);
    }
}