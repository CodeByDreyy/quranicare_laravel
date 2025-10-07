<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuranSurah;
use App\Models\QuranAyah;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class QuranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Menggunakan API Alquran Indonesia untuk mendapatkan data lengkap Al-Quran
        $this->seedQuranFromAPI();
    }

    private function seedQuranFromAPI()
    {
        try {
            // Ambil daftar semua surah dari API
            $response = Http::get('https://api.quran.gading.dev/surah');
            
            if ($response->successful()) {
                $surahList = $response->json()['data'];
                
                foreach ($surahList as $surahData) {
                                        // Buat atau update data surah
                    $surah = QuranSurah::updateOrCreate(
                        ['number' => $surahData['number']],
                        [
                            'name_arabic' => $surahData['name']['short'],
                            'name_indonesian' => $surahData['name']['translation']['id'],
                            'name_english' => $surahData['name']['translation']['en'] ?? $surahData['name']['translation']['id'],
                            'name_latin' => $surahData['name']['transliteration']['id'],
                            'number_of_ayahs' => $surahData['numberOfVerses'],
                            'place' => $surahData['revelation']['id'] === 'Makkah' ? 'Meccan' : 'Medinan',
                            'description_indonesian' => $surahData['tafsir']['id'] ?? '',
                            'description_english' => null,
                            'audio_url' => null // Will be filled later if needed
                        ]
                    );

                    // Ambil detail surah beserta ayat-ayatnya
                    $this->seedAyahForSurah($surah);
                    
                    // Progress indicator
                    $this->command->info("Seeded Surah {$surah->number}: {$surah->name_latin}");
                }
                
                $this->command->info("All Quranic data seeded successfully!");
            } else {
                $this->command->error("Failed to fetch Quran data from API");
                $this->seedStaticQuranData(); // Fallback to static data
            }
        } catch (\Exception $e) {
            $this->command->error("Error: " . $e->getMessage());
            $this->seedStaticQuranData(); // Fallback to static data
        }
    }

    private function seedAyahForSurah(QuranSurah $surah)
    {
        try {
            // Ambil detail surah dari API
            $response = Http::get("https://api.quran.gading.dev/surah/{$surah->number}");
            
            if ($response->successful()) {
                $surahDetail = $response->json()['data'];
                
                foreach ($surahDetail['verses'] as $verseData) {
                    QuranAyah::updateOrCreate(
                        [
                            'quran_surah_id' => $surah->id,
                            'number' => $verseData['number']['inSurah']
                        ],
                        [
                            'text_arabic' => $verseData['text']['arab'],
                            'text_indonesian' => $verseData['translation']['id'],
                            'text_english' => null, // Can be filled from other sources
                            'text_latin' => $verseData['text']['transliteration']['en'] ?? '',
                            'tafsir_indonesian' => null, // Can be filled from other sources
                            'tafsir_english' => null,
                            'audio_url' => $verseData['audio']['primary'] ?? null,
                            'keywords' => json_encode([
                                'juz' => $verseData['meta']['juz'],
                                'page' => $verseData['meta']['page'],
                                'manzil' => $verseData['meta']['manzil'],
                                'ruku' => $verseData['meta']['ruku'],
                                'hizb_quarter' => $verseData['meta']['hizbQuarter'],
                                'sajda' => $verseData['meta']['sajda']['recommended'] ?? null
                            ])
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->command->error("Error seeding ayah for surah {$surah->number}: " . $e->getMessage());
        }
    }

    /**
     * Fallback static data jika API tidak tersedia
     */
    private function seedStaticQuranData()
    {
        $this->command->info("Using static Quran data as fallback...");
        
        // Data beberapa surah penting untuk fallback
        $staticSurahs = [
            [
                'number' => 1,
                'name_arabic' => 'الفاتحة',
                'name_latin' => 'Al-Fatihah',
                'name_indonesian' => 'Pembuka',
                'name_english' => 'The Opener',
                'place' => 'Meccan',
                'number_of_ayahs' => 7,
                'description_indonesian' => 'Surah Al-Fatihah adalah surah pembuka Al-Quran yang wajib dibaca dalam setiap shalat.',
                'ayahs' => [
                    [
                        'ayah_number' => 1,
                        'text_arabic' => 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ',
                        'text_latin' => 'Bismillahirrahmanirrahim',
                        'text_translation' => 'Dengan nama Allah Yang Maha Pengasih, Maha Penyayang.',
                        'juz_number' => 1,
                        'page_number' => 1
                    ],
                    [
                        'ayah_number' => 2,
                        'text_arabic' => 'الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ',
                        'text_latin' => 'Alhamdulillahi rabbil alamiin',
                        'text_translation' => 'Segala puji bagi Allah, Tuhan semesta alam.',
                        'juz_number' => 1,
                        'page_number' => 1
                    ],
                    [
                        'ayah_number' => 3,
                        'text_arabic' => 'الرَّحْمَنِ الرَّحِيمِ',
                        'text_latin' => 'Arrahmanirrahim',
                        'text_translation' => 'Yang Maha Pengasih, Maha Penyayang.',
                        'juz_number' => 1,
                        'page_number' => 1
                    ],
                    [
                        'ayah_number' => 4,
                        'text_arabic' => 'مَالِكِ يَوْمِ الدِّينِ',
                        'text_latin' => 'Maaliki yaumiddin',
                        'text_translation' => 'Pemilik hari pembalasan.',
                        'juz_number' => 1,
                        'page_number' => 1
                    ],
                    [
                        'ayah_number' => 5,
                        'text_arabic' => 'إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ',
                        'text_latin' => 'Iyyaaka na\'budu wa iyyaaka nasta\'iin',
                        'text_translation' => 'Hanya kepada Engkaulah kami menyembah dan hanya kepada Engkaulah kami mohon pertolongan.',
                        'juz_number' => 1,
                        'page_number' => 1
                    ],
                    [
                        'ayah_number' => 6,
                        'text_arabic' => 'اهْدِنَا الصِّرَاطَ الْمُسْتَقِيمَ',
                        'text_latin' => 'Ihdinashiratal mustaqiim',
                        'text_translation' => 'Tunjukilah kami jalan yang lurus.',
                        'juz_number' => 1,
                        'page_number' => 1
                    ],
                    [
                        'ayah_number' => 7,
                        'text_arabic' => 'صِرَاطَ الَّذِينَ أَنْعَمْتَ عَلَيْهِمْ غَيْرِ الْمَغْضُوبِ عَلَيْهِمْ وَلَا الضَّالِّينَ',
                        'text_latin' => 'Shiratal laziina an\'amta \'alaihim ghairil maghdhuubi \'alaihim waladh dhaallin',
                        'text_translation' => '(yaitu) jalan orang-orang yang telah Engkau beri nikmat kepadanya; bukan (jalan) mereka yang dimurkai, dan bukan (pula jalan) mereka yang sesat.',
                        'juz_number' => 1,
                        'page_number' => 1
                    ]
                ]
            ],
            [
                'number' => 112,
                'name_arabic' => 'الإخلاص',
                'name_latin' => 'Al-Ikhlas',
                'name_indonesian' => 'Ikhlas',
                'name_english' => 'The Sincerity',
                'place' => 'Meccan',
                'number_of_ayahs' => 4,
                'description_indonesian' => 'Surah Al-Ikhlas menjelaskan tentang keesaan Allah SWT.',
                'ayahs' => [
                    [
                        'ayah_number' => 1,
                        'text_arabic' => 'قُلْ هُوَ اللَّهُ أَحَدٌ',
                        'text_latin' => 'Qul huwallahu ahad',
                        'text_translation' => 'Katakanlah: "Dia-lah Allah, Yang Maha Esa.',
                        'juz_number' => 30,
                        'page_number' => 604
                    ],
                    [
                        'ayah_number' => 2,
                        'text_arabic' => 'اللَّهُ الصَّمَدُ',
                        'text_latin' => 'Allahush shamad',
                        'text_translation' => 'Allah adalah Tuhan yang bergantung kepada-Nya segala sesuatu.',
                        'juz_number' => 30,
                        'page_number' => 604
                    ],
                    [
                        'ayah_number' => 3,
                        'text_arabic' => 'لَمْ يَلِدْ وَلَمْ يُولَدْ',
                        'text_latin' => 'Lam yalid wa lam yuulad',
                        'text_translation' => 'Dia tiada beranak dan tidak pula diperanakkan.',
                        'juz_number' => 30,
                        'page_number' => 604
                    ],
                    [
                        'ayah_number' => 4,
                        'text_arabic' => 'وَلَمْ يَكُنْ لَهُ كُفُوًا أَحَدٌ',
                        'text_latin' => 'Wa lam yakul lahu kufuwan ahad',
                        'text_translation' => 'Dan tidak ada seorang pun yang setara dengan Dia."',
                        'juz_number' => 30,
                        'page_number' => 604
                    ]
                ]
            ]
        ];

        foreach ($staticSurahs as $surahData) {
            $surah = QuranSurah::create([
                'number' => $surahData['surah_number'],
                'name_arabic' => $surahData['name_arabic'],
                'name_indonesian' => $surahData['name_translation'],
                'name_english' => $surahData['name_translation'],
                'name_latin' => $surahData['name_latin'],
                'number_of_ayahs' => $surahData['total_ayah'],
                'place' => $surahData['revelation_place'] === 'Makkah' ? 'Meccan' : 'Medinan',
                'description_indonesian' => $surahData['description'],
                'description_english' => null,
                'audio_url' => null
            ]);

            foreach ($surahData['ayahs'] as $ayahData) {
                QuranAyah::create([
                    'quran_surah_id' => $surah->id,
                    'number' => $ayahData['ayah_number'],
                    'text_arabic' => $ayahData['text_arabic'],
                    'text_indonesian' => $ayahData['text_translation'],
                    'text_english' => null,
                    'text_latin' => $ayahData['text_latin'],
                    'tafsir_indonesian' => null,
                    'tafsir_english' => null,
                    'audio_url' => null,
                    'keywords' => json_encode([
                        'juz' => $ayahData['juz_number'],
                        'page' => $ayahData['page_number']
                    ])
                ]);
            }
        }
    }
}
