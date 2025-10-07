<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DoaDzikir;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DzikirSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Fetching doa and dzikir from equran.id API...');
        
        try {
            // Fetch data from equran.id API
            $response = Http::timeout(60)->get('https://equran.id/api/doa');
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Debug: Log the response structure
                Log::info('API Response structure:', ['sample' => array_slice($responseData, 0, 2)]);
                $this->command->info('Raw API response: ' . json_encode(array_slice($responseData, 0, 1)));
                
                // Handle API response structure: {status: "success", total: 228, data: [...]}
                $doaData = [];
                if (isset($responseData['data']) && is_array($responseData['data'])) {
                    $doaData = $responseData['data'];
                } else if (is_array($responseData) && !isset($responseData['status'])) {
                    // Fallback if it's direct array
                    $doaData = $responseData;
                }
                
                if (empty($doaData)) {
                    $this->command->error('No data found in API response');
                    return;
                }
                
                $this->command->info('Processing ' . count($doaData) . ' doa and dzikir items...');
                
                $progressBar = $this->command->getOutput()->createProgressBar(count($doaData));
                $progressBar->start();
                
                foreach ($doaData as $index => $item) {
                    // Skip if item is not array or object
                    if (!is_array($item) && !is_object($item)) {
                        $this->command->warn("Skipping invalid item at index {$index}: " . gettype($item));
                        $progressBar->advance();
                        continue;
                    }
                    
                    // Convert object to array if needed
                    $itemArray = is_object($item) ? (array) $item : $item;
                    
                    // Check if item already exists based on API ID
                    $apiId = $itemArray['id'] ?? null;
                    if (!$apiId) {
                        $this->command->warn("Skipping item without ID at index {$index}");
                        $progressBar->advance();
                        continue;
                    }
                    
                    $existingDoa = DoaDzikir::where('api_id', $apiId)->first();
                    
                    if (!$existingDoa) {
                        try {
                            DoaDzikir::create([
                                'grup' => $itemArray['grup'] ?? 'Umum',
                                'nama' => $itemArray['nama'] ?? 'Dzikir',
                                'ar' => $itemArray['ar'] ?? '',
                                'tr' => $itemArray['tr'] ?? '',
                                'idn' => $itemArray['idn'] ?? '',
                                'tentang' => $itemArray['tentang'] ?? '',
                                'tag' => is_array($itemArray['tag'] ?? []) ? $itemArray['tag'] : [$itemArray['tag'] ?? 'umum'],
                                'api_id' => $apiId,
                                'is_active' => true,
                                'is_featured' => $this->shouldBeFeatured($itemArray),
                            ]);
                        } catch (\Exception $e) {
                            $this->command->error("Failed to create item {$apiId}: " . $e->getMessage());
                            Log::error('Failed to create DoaDzikir', ['item' => $itemArray, 'error' => $e->getMessage()]);
                        }
                    }
                    
                    $progressBar->advance();
                }
                
                $progressBar->finish();
                $this->command->newLine();
                $this->command->info('Successfully imported doa and dzikir from equran.id API!');
                
                // Show statistics
                $totalCount = DoaDzikir::count();
                $featuredCount = DoaDzikir::where('is_featured', true)->count();
                $groupsCount = DoaDzikir::distinct('grup')->count();
                
                $this->command->table(
                    ['Metric', 'Count'],
                    [
                        ['Total Doa & Dzikir', $totalCount],
                        ['Featured Items', $featuredCount],
                        ['Groups', $groupsCount],
                    ]
                );
                
            } else {
                $this->command->error('Failed to fetch data from equran.id API. Status: ' . $response->status());
                Log::error('Failed to fetch doa data from API', ['status' => $response->status()]);
            }
            
        } catch (\Exception $e) {
            $this->command->error('Error fetching data: ' . $e->getMessage());
            Log::error('Error in DzikirSeeder', ['error' => $e->getMessage()]);
            
            // Fallback to manual data if API fails
            $this->command->info('Falling back to manual seed data...');
            $this->seedFallbackData();
        }
    }
    
    /**
     * Determine if a doa/dzikir should be featured
     */
    private function shouldBeFeatured($item): bool
    {
        // Feature items from specific popular groups
        $featuredGroups = [
            'Lafal Dzikir Dan Keutamaannya',
            'Istighfar Dan Taubat',
            'Doa Sebelum Tidur',
            'Doa Bangun Tidur',
            'Doa Masuk Dan Keluar Kamar Mandi',
            'Doa Makan Dan Minum'
        ];
        
        if (in_array($item['grup'], $featuredGroups)) {
            return true;
        }
        
        // Feature items with specific popular names
        $featuredNames = [
            'Subhanallah',
            'Alhamdulillah', 
            'Allahu Akbar',
            'Astaghfirullah',
            'Ayat Kursi',
            'Surah Al-Ikhlas',
            'Doa Istighfar/Taubat 1',
            'Lafal Dzikir 1'
        ];
        
        foreach ($featuredNames as $name) {
            if (stripos($item['nama'], $name) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Fallback manual data if API fails
     */
    private function seedFallbackData(): void
    {
        $fallbackData = [
            [
                'grup' => 'Lafal Dzikir Dan Keutamaannya',
                'nama' => 'Subhanallah wa bihamdihi',
                'ar' => 'سُبْحَانَ اللَّهِ وَبِحَمْدِهِ',
                'tr' => 'Subhaanallaahi wa bihamdih',
                'idn' => 'Maha Suci Allah, aku memujiNya',
                'tentang' => 'HR. Al-Bukhari 7/168, Muslim 4/2071. Nabi Shallallahu\'alaihi wasallam bersabda: "Siapa yang membaca dzikir ini 100x dalam sehari, maka akan dihapuskan dosa-dosanya, meskipun sebanyak buih di lautan."',
                'tag' => ['umum'],
                'api_id' => 9999001,
                'is_featured' => true,
            ],
            [
                'grup' => 'Istighfar Dan Taubat',
                'nama' => 'Astaghfirullah Al-Adzim',
                'ar' => 'أَسْتَغْفِرُ اللَّهَ الْعَظِيْمَ الَّذِيْ لاَ إِلَـٰهَ إِلاَّ هُوَ الْحَيُّ الْقَيُّوْمُ وَأَتُوْبُ إِلَيْهِ',
                'tr' => 'Astaghfirullaahal \'azhiimal-ladzii laa ilaaha illaa huwal hayyul qoyyuum, wa atuubu ilaih',
                'idn' => 'Aku minta ampun kepada Allah Yang Maha Agung, tidak ada sesembahan yang berhak disembah kecuali Dia, Yang Hidup dan terus-menerus mengurus makhlukNya, dan aku bertaubat kepada-Nya',
                'tentang' => 'HR. Abu Dawud 2/85, At-Tirmidzi 5/569. Rasul Shallallahu\'alaihi wasallam bersabda: "Barangsiapa yang membaca doa ini maka Allah mengampuninya, sekalipun dia pernah lari dari perang."',
                'tag' => ['umum'],
                'api_id' => 9999002,
                'is_featured' => true,
            ],
            [
                'grup' => 'Doa Sebelum Tidur',
                'nama' => 'Doa Sebelum Tidur',
                'ar' => 'بِاسْمِكَ رَبِّيْ وَضَعْتُ جَنْبِيْ، وَبِكَ أَرْفَعُهُ، فَإِنْ أَمْسَكْتَ نَفْسِيْ فَارْحَمْهَا، وَإِنْ أَرْسَلْتَهَا فَاحْفَظْهَا بِمَا تَحْفَظُ بِهِ عِبَادَكَ الصَّالِحِيْنَ',
                'tr' => 'Bismika robbii wadha\'tu janbii, wa bika arfa\'uh, fa in amsakta nafsii farhamhaa, wa in arsaltahaa fahfazhhaa bimaa tahfazhu bihi \'ibaadakash-shaalihiin',
                'idn' => 'Dengan nama-Mu ya Tuhanku aku meletakkan lambungku, dan dengan (kekuatan)-Mu aku mengangkatnya. Jika Engkau tahan jiwaku, maka rahmatilah dia, dan jika Engkau melepasnya, maka peliharalah dia dengan apa yang Engkau pelihara hamba-hamba-Mu yang shalih',
                'tentang' => 'HR. Al-Bukhari 11/113, Muslim 4/2084.',
                'tag' => ['tidur', 'malam'],
                'api_id' => 9999003,
                'is_featured' => true,
            ]
        ];
        
        foreach ($fallbackData as $data) {
            DoaDzikir::firstOrCreate(['api_id' => $data['api_id']], $data);
        }
        
        $this->command->info('Fallback data seeded successfully!');
    }
}
