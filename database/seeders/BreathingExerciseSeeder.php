<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BreathingCategory;
use App\Models\BreathingExercise;

class BreathingExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create breathing categories
        $categories = [
            [
                'name' => 'Dzikir Pagi',
                'description' => 'Latihan pernapasan dengan dzikir untuk memulai hari',
                'icon' => 'sunrise.png',
                'is_active' => true
            ],
            [
                'name' => 'Dzikir Sore', 
                'description' => 'Latihan pernapasan dengan dzikir untuk mengakhiri hari',
                'icon' => 'sunset.png',
                'is_active' => true
            ],
            [
                'name' => 'Dzikir Ketenangan',
                'description' => 'Latihan pernapasan untuk menenangkan hati dan pikiran',
                'icon' => 'peace.png', 
                'is_active' => true
            ],
            [
                'name' => 'Dzikir Syukur',
                'description' => 'Latihan pernapasan dengan dzikir rasa syukur',
                'icon' => 'gratitude.png',
                'is_active' => true
            ]
        ];

        foreach ($categories as $categoryData) {
            $category = BreathingCategory::create($categoryData);
            $this->createExercisesForCategory($category);
        }
    }

    private function createExercisesForCategory(BreathingCategory $category)
    {
        $exercises = [];

        switch ($category->name) {
            case 'Dzikir Pagi':
                $exercises = [
                    [
                        'name' => 'Subhanallah Pagi',
                        'description' => 'Memulai hari dengan menyucikan nama Allah',
                        'dzikir_text' => 'سُبْحَانَ اللَّهِ',
                        'audio_path' => 'audio/dzikir/subhanallah.mp3',
                        'inhale_duration' => 2,
                        'hold_duration' => 3,
                        'exhale_duration' => 3,
                        'total_cycle_duration' => 8,
                        'default_repetitions' => 7,
                        'is_active' => true
                    ],
                    [
                        'name' => 'Alhamdulillah Pagi',
                        'description' => 'Memuji Allah di pagi hari',
                        'dzikir_text' => 'الْحَمْدُ لِلَّهِ',
                        'audio_path' => 'audio/dzikir/alhamdulillah.mp3',
                        'inhale_duration' => 2,
                        'hold_duration' => 3,
                        'exhale_duration' => 3,
                        'total_cycle_duration' => 8,
                        'default_repetitions' => 7,
                        'is_active' => true
                    ]
                ];
                break;

            case 'Dzikir Sore':
                $exercises = [
                    [
                        'name' => 'Astaghfirullah Sore',
                        'description' => 'Memohon ampun Allah di sore hari',
                        'dzikir_text' => 'أَسْتَغْفِرُ اللَّهَ',
                        'audio_path' => 'audio/dzikir/astaghfirullah.mp3',
                        'inhale_duration' => 2,
                        'hold_duration' => 3,
                        'exhale_duration' => 3,
                        'total_cycle_duration' => 8,
                        'default_repetitions' => 10,
                        'is_active' => true
                    ],
                    [
                        'name' => 'La Hawla Wa La Quwwata',
                        'description' => 'Menyerahkan segala urusan kepada Allah',
                        'dzikir_text' => 'لَا حَوْلَ وَلَا قُوَّةَ إِلَّا بِاللَّهِ',
                        'audio_path' => 'audio/dzikir/la_hawla.mp3',
                        'inhale_duration' => 3,
                        'hold_duration' => 2,
                        'exhale_duration' => 3,
                        'total_cycle_duration' => 8,
                        'default_repetitions' => 7,
                        'is_active' => true
                    ]
                ];
                break;

            case 'Dzikir Ketenangan':
                $exercises = [
                    [
                        'name' => 'La Ilaha Illallah',
                        'description' => 'Menenangkan hati dengan kalimat tauhid',
                        'dzikir_text' => 'لَا إِلَٰهَ إِلَّا اللَّهُ',
                        'audio_path' => 'audio/dzikir/la_ilaha_illallah.mp3',
                        'inhale_duration' => 2,
                        'hold_duration' => 3,
                        'exhale_duration' => 3,
                        'total_cycle_duration' => 8,
                        'default_repetitions' => 10,
                        'is_active' => true
                    ],
                    [
                        'name' => 'Allahu Akbar',
                        'description' => 'Mengagungkan Allah untuk ketenangan jiwa',
                        'dzikir_text' => 'اللَّهُ أَكْبَرُ',
                        'audio_path' => 'audio/dzikir/allahu_akbar.mp3',
                        'inhale_duration' => 2,
                        'hold_duration' => 3,
                        'exhale_duration' => 3,
                        'total_cycle_duration' => 8,
                        'default_repetitions' => 7,
                        'is_active' => true
                    ],
                    [
                        'name' => 'Robbana Atina',
                        'description' => 'Doa kebaikan dunia dan akhirat',
                        'dzikir_text' => 'رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً',
                        'audio_path' => 'audio/dzikir/robbana_atina.mp3',
                        'inhale_duration' => 3,
                        'hold_duration' => 2,
                        'exhale_duration' => 3,
                        'total_cycle_duration' => 8,
                        'default_repetitions' => 5,
                        'is_active' => true
                    ]
                ];
                break;

            case 'Dzikir Syukur':
                $exercises = [
                    [
                        'name' => 'Alhamdulillahi Rabbil Alamiin',
                        'description' => 'Bersyukur kepada Allah Tuhan semesta alam',
                        'dzikir_text' => 'الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ',
                        'audio_path' => 'audio/dzikir/alhamdulillahi_rabbil_alamiin.mp3',
                        'inhale_duration' => 3,
                        'hold_duration' => 2,
                        'exhale_duration' => 3,
                        'total_cycle_duration' => 8,
                        'default_repetitions' => 7,
                        'is_active' => true
                    ],
                    [
                        'name' => 'Barakallahu Lana',
                        'description' => 'Memohon keberkahan dari Allah',
                        'dzikir_text' => 'بَارَكَ اللَّهُ لَنَا',
                        'audio_path' => 'audio/dzikir/barakallahu_lana.mp3',
                        'inhale_duration' => 2,
                        'hold_duration' => 3,
                        'exhale_duration' => 3,
                        'total_cycle_duration' => 8,
                        'default_repetitions' => 10,
                        'is_active' => true
                    ]
                ];
                break;
        }

        foreach ($exercises as $exerciseData) {
            BreathingExercise::create([
                'breathing_category_id' => $category->id,
                ...$exerciseData
            ]);
        }
    }
}
