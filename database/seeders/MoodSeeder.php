<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Mood;
use App\Models\MoodStatistic;
use App\Models\User;
use Carbon\Carbon;

class MoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->info('No users found. Please run UserSeeder first.');
            return;
        }

        // Mood types with their frequencies (more positive moods)
        $moodTypes = [
            'senang' => 35,      // 35% chance
            'biasa_saja' => 25,  // 25% chance
            'sedih' => 15,       // 15% chance
            'murung' => 15,      // 15% chance
            'marah' => 10,       // 10% chance
        ];

        // Sample notes for each mood type
        $moodNotes = [
            'senang' => [
                'Alhamdulillah, hari ini sangat menyenangkan',
                'Bersyukur atas nikmat Allah hari ini',
                'Feeling blessed today!',
                'Berhasil menyelesaikan tugas dengan baik',
                'Bertemu teman lama, sangat menyenangkan',
                'Dapat kabar baik dari keluarga',
                'Sholat berjamaah di masjid, hati tenang',
                null // sometimes no notes
            ],
            'biasa_saja' => [
                'Hari yang normal seperti biasanya',
                'Tidak ada yang spesial hari ini',
                'Rutinitas seperti biasa',
                'Biasa aja sih',
                'Just another day',
                null,
                null
            ],
            'sedih' => [
                'Merasa sedikit sedih hari ini',
                'Ada masalah keluarga yang membuat khawatir',
                'Kehilangan seseorang yang disayang',
                'Gagal dalam ujian/presentasi',
                'Merasa kesepian',
                'Cuaca mendung, ikut sedih',
                null
            ],
            'murung' => [
                'Tidak mood untuk beraktivitas',
                'Merasa lelah secara mental',
                'Overthinking tentang masa depan',
                'Stress dengan pekerjaan',
                'Merasa tidak produktif hari ini',
                'Butuh me-time',
                null
            ],
            'marah' => [
                'Kesal dengan situasi yang tidak adil',
                'Marah karena ada yang tidak bertanggung jawab',
                'Traffic jam sangat menyebalkan',
                'Konflik dengan rekan kerja',
                'Pelayanan yang mengecewakan',
                'Sabar... sabar...',
                null
            ]
        ];

        // Create mood data for the last 30 days for each user
        foreach ($users as $user) {
            $this->command->info("Creating mood data for user: {$user->name}");

            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                
                // Some days user might not record mood (70% chance to record)
                if (rand(1, 100) <= 70) {
                    // Random number of mood entries per day (1-3 entries)
                    $entriesCount = rand(1, 3);
                    
                    for ($j = 0; $j < $entriesCount; $j++) {
                        // Select mood type based on weighted probability
                        $moodType = $this->getWeightedRandomMood($moodTypes);
                        
                        // Random time during the day
                        $hour = rand(6, 22); // Between 6 AM and 10 PM
                        $minute = rand(0, 59);
                        $time = sprintf('%02d:%02d:00', $hour, $minute);
                        
                        // Get random note for this mood type
                        $notes = $moodNotes[$moodType];
                        $note = $notes[array_rand($notes)];

                        Mood::create([
                            'user_id' => $user->id,
                            'mood_type' => $moodType,
                            'notes' => $note,
                            'mood_date' => $date->toDateString(),
                            'mood_time' => $time,
                            'created_at' => $date->setTime($hour, $minute),
                            'updated_at' => $date->setTime($hour, $minute),
                        ]);
                    }

                    // Update mood statistics for this date
                    $this->updateMoodStatistics($user->id, $date->toDateString());
                }
            }
        }

        $this->command->info('Mood seeder completed successfully!');
    }

    /**
     * Get weighted random mood type
     */
    private function getWeightedRandomMood(array $weights): string
    {
        $rand = rand(1, 100);
        $currentWeight = 0;
        
        foreach ($weights as $mood => $weight) {
            $currentWeight += $weight;
            if ($rand <= $currentWeight) {
                return $mood;
            }
        }
        
        return 'biasa_saja'; // fallback
    }

    /**
     * Update mood statistics for a specific date
     */
    private function updateMoodStatistics(int $userId, string $date): void
    {
        $moods = Mood::where('user_id', $userId)
            ->where('mood_date', $date)
            ->get();

        if ($moods->isEmpty()) {
            return;
        }

        $moodCounts = $moods->countBy('mood_type')->toArray();
        
        // Fill missing mood types with 0
        foreach (['senang', 'sedih', 'biasa_saja', 'marah', 'murung'] as $moodType) {
            if (!isset($moodCounts[$moodType])) {
                $moodCounts[$moodType] = 0;
            }
        }

        $dominantMood = collect($moodCounts)->sortDesc()->keys()->first();
        
        // Calculate mood score (simple algorithm: positive moods = higher score)
        $moodScore = 0;
        if ($moods->count() > 0) {
            $scores = [
                'senang' => 5,
                'biasa_saja' => 3,
                'sedih' => 2,
                'murung' => 1,
                'marah' => 1
            ];
            
            $totalScore = 0;
            foreach ($moodCounts as $mood => $count) {
                $totalScore += $scores[$mood] * $count;
            }
            $moodScore = round($totalScore / $moods->count(), 2);
        }

        MoodStatistic::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'mood_counts' => json_encode($moodCounts),
                'dominant_mood' => $dominantMood,
                'mood_score' => $moodScore,
                'total_entries' => $moods->count()
            ]
        );
    }
}