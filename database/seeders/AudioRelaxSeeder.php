<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AudioCategory;
use App\Models\AudioRelax;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AudioRelaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create audio categories
        $categories = [
            [
                'name' => 'Tilawah Al-Quran',
                'description' => 'Bacaan Al-Quran dengan suara merdu untuk ketenangan jiwa',
                'icon' => 'quran_audio.png',
                'color_code' => '#2ECC71',
                'is_active' => true
            ],
            [
                'name' => 'Dzikir dan Doa',
                'description' => 'Audio dzikir dan doa-doa pilihan untuk ketenangan hati',
                'icon' => 'dzikir_audio.png',
                'color_code' => '#3498DB',
                'is_active' => true
            ],
            [
                'name' => 'Suara Alam',
                'description' => 'Suara alam yang menenangkan untuk relaksasi',
                'icon' => 'nature_audio.png',
                'color_code' => '#1ABC9C',
                'is_active' => true
            ],
            [
                'name' => 'Musik Islami',
                'description' => 'Musik dan nasyid Islami yang menyejukkan hati',
                'icon' => 'islamic_music.png',
                'color_code' => '#9B59B6',
                'is_active' => true
            ],
            [
                'name' => 'Murottal',
                'description' => 'Murottal Al-Quran dari berbagai qari terkenal',
                'icon' => 'murottal.png',
                'color_code' => '#E74C3C',
                'is_active' => true
            ]
        ];

        foreach ($categories as $categoryData) {
            $category = AudioCategory::create($categoryData);
            $this->createAudioForCategory($category);
        }
    }

    /**
     * Download file from Google Drive and save to storage
     */
    private function downloadFromGoogleDrive($fileId, $localPath, $fileName)
    {
        try {
            // Cek apakah ini folder ID (tidak bisa didownload langsung)
            if ($fileId === '1t8jZaFoOJUoCr9nqSaO6GHcn47PP5yIY') {
                $this->command->warn("Skipping {$fileName} - folder ID provided, need specific file ID");
                return $this->createPlaceholderPath($localPath, $fileName);
            }

            $url = "https://drive.google.com/uc?export=download&id={$fileId}";
            
            $this->command->info("Attempting to download from: {$url}");
            
            $response = Http::timeout(300)->get($url);
            
            if ($response->successful()) {
                // Cek apakah response adalah HTML (biasanya error page)
                $contentType = $response->header('content-type');
                if (str_contains($contentType, 'text/html')) {
                    $this->command->warn("Failed to download {$fileName} - received HTML instead of file (possibly private file)");
                    return $this->createPlaceholderPath($localPath, $fileName);
                }

                $fullPath = $localPath . '/' . $fileName;
                Storage::disk('public')->put($fullPath, $response->body());
                
                // Detect actual file format
                $storagePath = storage_path('app/public/' . $fullPath);
                $actualFormat = $this->detectAudioFormat($storagePath);
                
                if ($actualFormat !== 'mp3') {
                    $this->command->info("🔄 File is actually {$actualFormat} format, keeping as {$fileName}");
                    $this->command->warn("⚠️  Note: {$fileName} is {$actualFormat} format, not MP3. Some players may not support it.");
                }
                
                $this->command->info("✅ Downloaded: {$fileName} to storage/{$fullPath} ({$actualFormat} format)");
                return 'storage/' . $fullPath;
            } else {
                $this->command->warn("❌ Failed to download: {$fileName} - HTTP {$response->status()}");
                return $this->createPlaceholderPath($localPath, $fileName);
            }
        } catch (\Exception $e) {
            $this->command->error("💥 Error downloading {$fileName}: " . $e->getMessage());
            return $this->createPlaceholderPath($localPath, $fileName);
        }
    }

    /**
     * Detect actual audio format of downloaded file
     */
    private function detectAudioFormat($filePath)
    {
        if (!file_exists($filePath)) {
            return 'unknown';
        }

        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 12);
        fclose($handle);

        // Check for MP3 (ID3 or MPEG header)
        if (substr($header, 0, 3) === 'ID3' || substr($header, 0, 2) === "\xFF\xFB") {
            return 'mp3';
        }
        
        // Check for Ogg
        if (substr($header, 0, 4) === 'OggS') {
            return 'ogg';
        }
        
        // Check for AAC/M4A
        if (substr($header, 4, 4) === 'ftyp') {
            return 'aac';
        }
        
        // Check for WAV
        if (substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WAVE') {
            return 'wav';
        }

        return 'unknown';
    }

    /**
     * Create placeholder path for failed downloads
     */
    private function createPlaceholderPath($localPath, $fileName)
    {
        $placeholderPath = $localPath . '/' . $fileName;
        
        // Create empty file as placeholder
        Storage::disk('public')->put($placeholderPath, '');
        
        $this->command->info("📝 Created placeholder: storage/{$placeholderPath}");
        return 'storage/' . $placeholderPath;
    }

    /**
     * Create default thumbnail if not available
     */
    private function createDefaultThumbnail($category, $fileName)
    {
        $defaultThumbnails = [
            'Musik Islami' => 'thumbnails/default_nasheed.jpg',
            'Tilawah Al-Quran' => 'thumbnails/default_quran.jpg',
            'Dzikir dan Doa' => 'thumbnails/default_dzikir.jpg',
            'Suara Alam' => 'thumbnails/default_nature.jpg',
            'Murottal' => 'thumbnails/default_murottal.jpg'
        ];

        return $defaultThumbnails[$category] ?? 'thumbnails/default_audio.jpg';
    }

    private function createAudioForCategory(AudioCategory $category)
    {
        $audios = [];

        switch ($category->name) {
            case 'Tilawah Al-Quran':
                $audios = [
                    [
                        'title' => 'Surah Al-Fatihah - Sheikh Abdul Rahman Al-Sudais',
                        'description' => 'Bacaan Surah Al-Fatihah dengan suara merdu Sheikh Abdul Rahman Al-Sudais',
                        'audio_path' => 'audio/tilawah/al_fatiha_sudais.mp3',
                        'duration_seconds' => 120,
                        'thumbnail_path' => 'thumbnails/al_fatiha.jpg',
                        'artist' => 'Sheikh Abdul Rahman Al-Sudais',
                        'download_count' => 0,
                        'play_count' => 0,
                        'rating' => 0,
                        'rating_count' => 0,
                        'is_premium' => false,
                        'is_active' => true
                    ],
                    [
                        'title' => 'Surah Al-Ikhlas - Sheikh Saad Al-Ghamdi',
                        'description' => 'Bacaan Surah Al-Ikhlas dengan suara Sheikh Saad Al-Ghamdi',
                        'audio_path' => 'audio/tilawah/al_ikhlas_ghamdi.mp3',
                        'duration_seconds' => 60,
                        'thumbnail_path' => 'thumbnails/al_ikhlas.jpg',
                        'artist' => 'Sheikh Saad Al-Ghamdi',
                        'download_count' => 0,
                        'play_count' => 0,
                        'rating' => 0,
                        'rating_count' => 0,
                        'is_premium' => false,
                        'is_active' => true
                    ]
                ];
                break;

            case 'Dzikir dan Doa':
                $audios = [
                    [
                        'title' => 'Dzikir Subhanallah 100x',
                        'description' => 'Dzikir Subhanallah diulang 100 kali dengan irama yang menenangkan',
                        'audio_path' => 'audio/dzikir/subhanallah_100x.mp3',
                        'duration_seconds' => 300,
                        'thumbnail_path' => 'thumbnails/subhanallah.jpg',
                        'artist' => 'Ustadz Abdullah',
                        'download_count' => 0,
                        'play_count' => 0,
                        'rating' => 0,
                        'rating_count' => 0,
                        'is_premium' => false,
                        'is_active' => true
                    ]
                ];
                break;

            case 'Suara Alam':
                $audios = [
                    [
                        'title' => 'Suara Hujan Gerimis',
                        'description' => 'Suara hujan gerimis yang menenangkan untuk relaksasi',
                        'audio_path' => 'audio/nature/rain_light.mp3',
                        'duration_seconds' => 1800,
                        'thumbnail_path' => 'thumbnails/rain.jpg',
                        'artist' => null,
                        'download_count' => 0,
                        'play_count' => 0,
                        'rating' => 0,
                        'rating_count' => 0,
                        'is_premium' => false,
                        'is_active' => true
                    ]
                ];
                break;

            case 'Musik Islami':
                $googleDriveFiles = [
                    [
                        'title' => 'Astaghfirullah Nasheed',
                        'description' => 'Nasyid Islami yang mengajak untuk beristighfar dan memohon ampunan Allah',
                        'google_drive_id' => '1HKUmVyCWXff8829cnT3lZM96Z0NIzMRn',
                        'file_name' => 'astaghfirullah_nasheed.mp3',
                        'duration_seconds' => 320,
                        'artist' => 'Islamic Audio Library'
                    ],
                    [
                        'title' => 'Sad Nasheed',
                        'description' => 'Nasyid dengan melodi sedih yang menyentuh hati dan mengajak untuk muhasabah',
                        'google_drive_id' => '1B38sMqLuBFF1p6Kt3APesvO0_z1-EgIj',
                        'file_name' => 'sad_nasheed.mp3',
                        'duration_seconds' => 280,
                        'artist' => 'Islamic Audio Library'
                    ],
                    [
                        'title' => 'People Like You Nasheed',
                        'description' => 'Nasyid inspiratif tentang kebaikan hati manusia dengan vocals only yang menyentuh jiwa',
                        'google_drive_id' => '18eJorDzb9ACE1YU2v1XZ85CbZn1VSFLB',
                        'file_name' => 'people_like_you_nasheed.mp3',
                        'duration_seconds' => 360,
                        'artist' => 'Shaib Alie ft Oneness of Islam'
                    ],
                    [
                        'title' => 'Allahu Allahu Nasheed',
                        'description' => 'Nasyid takbir yang mengagungkan nama Allah dengan melodi yang merdu',
                        'google_drive_id' => '1t0U3uP8u47iV0wGEhPkbY5hyol4NTc2d',
                        'file_name' => 'allahu_allahu_nasheed.mp3',
                        'duration_seconds' => 240,
                        'artist' => 'Ahmed Rajel'
                    ],
                    [
                        'title' => 'Joyful & Upbeat Nasheed',
                        'description' => 'Nasyid dengan tempo riang dan gembira yang memberikan semangat positif',
                        'google_drive_id' => '1Y9VtJYD_nPHie2aryR3kBLttADZrBhNR',
                        'file_name' => 'joyful_upbeat_nasheed.mp3',
                        'duration_seconds' => 300,
                        'artist' => 'Islamic Audio Library'
                    ],
                    [
                        'title' => 'Relaxing & Calm Nasheed',
                        'description' => 'Nasyid yang menenangkan dan meredakan pikiran untuk relaksasi spiritual',
                        'google_drive_id' => '1t5r_2LvCeQq_KGFMR8eT2dmdl0YYXKvU',
                        'file_name' => 'relaxing_calm_nasheed.mp3',
                        'duration_seconds' => 420,
                        'artist' => 'Islamic Audio Library'
                    ],
                    [
                        'title' => 'Humming Nasheed',
                        'description' => 'Nasyid dengan humming yang menenangkan dan menyejukkan hati',
                        'google_drive_id' => '1K1GXI9IZ4BhaQrA0xlzQd6W90u3fMV__',
                        'file_name' => 'humming_nasheed.mp3',
                        'duration_seconds' => 250,
                        'artist' => 'Islamic Audio Library'
                    ]
                ];

                // Process files dengan download dari Google Drive
                foreach ($googleDriveFiles as $fileData) {
                    $this->command->info("🎵 Processing: {$fileData['title']}");
                    
                    // Download audio file
                    $audioPath = $this->downloadFromGoogleDrive(
                        $fileData['google_drive_id'],
                        'audio/islamic_music',
                        $fileData['file_name']
                    );
                    
                    // Generate thumbnail path (default untuk sekarang)
                    $thumbnailName = str_replace('.mp3', '.jpg', $fileData['file_name']);
                    $thumbnailPath = $this->createDefaultThumbnail($category->name, $thumbnailName);
                    
                    $audios[] = [
                        'title' => $fileData['title'],
                        'description' => $fileData['description'],
                        'audio_path' => $audioPath,
                        'duration_seconds' => $fileData['duration_seconds'],
                        'thumbnail_path' => $thumbnailPath,
                        'artist' => $fileData['artist'],
                        'download_count' => 0,
                        'play_count' => 0,
                        'rating' => 0,
                        'rating_count' => 0,
                        'is_premium' => false,
                        'is_active' => true
                    ];
                }

                // Tambahkan juga audio yang sudah ada sebelumnya
                $audios[] = [
                    'title' => 'Shalawat Badar',
                    'description' => 'Shalawat Badar dengan melodi yang menyentuh hati',
                    'audio_path' => 'audio/islamic_music/shalawat_badar.mp3',
                    'duration_seconds' => 480,
                    'thumbnail_path' => 'thumbnails/shalawat_badar.jpg',
                    'artist' => 'Habib Syech bin Abdul Qodir Assegaf',
                    'download_count' => 0,
                    'play_count' => 0,
                    'rating' => 0,
                    'rating_count' => 0,
                    'is_premium' => false,
                    'is_active' => true
                ];
                break;

            case 'Murottal':
                $audios = [
                    [
                        'title' => 'Murottal Surah Ar-Rahman - Sheikh Abdul Basit',
                        'description' => 'Murottal Surah Ar-Rahman dengan bacaan yang sangat merdu',
                        'audio_path' => 'audio/murottal/ar_rahman_abdul_basit.mp3',
                        'duration_seconds' => 900,
                        'thumbnail_path' => 'thumbnails/ar_rahman.jpg',
                        'artist' => 'Sheikh Abdul Basit Abdul Samad',
                        'download_count' => 0,
                        'play_count' => 0,
                        'rating' => 0,
                        'rating_count' => 0,
                        'is_premium' => false,
                        'is_active' => true
                    ]
                ];
                break;
        }

        foreach ($audios as $audioData) {
            AudioRelax::create([
                'audio_category_id' => $category->id,
                ...$audioData
            ]);
        }
    }
}