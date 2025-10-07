<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PsychologyCategory;
use App\Models\PsychologyMaterial;
use Carbon\Carbon;

class PsychologyMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create psychology categories
        $categories = [
            [
                'name' => 'Manajemen Emosi',
                'description' => 'Materi tentang cara mengelola emosi dalam perspektif Islam',
                'icon' => 'emotions.png',
                'color_code' => '#FF6B6B',
                'sort_order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Stres dan Kecemasan',
                'description' => 'Cara mengatasi stres dan kecemasan menurut ajaran Islam',
                'icon' => 'stress.png', 
                'color_code' => '#4ECDC4',
                'sort_order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Kebahagiaan Islami',
                'description' => 'Menemukan kebahagiaan sejati melalui ajaran Islam',
                'icon' => 'happiness.png',
                'color_code' => '#45B7D1',
                'sort_order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Hubungan Sosial',
                'description' => 'Membangun hubungan yang baik dengan sesama',
                'icon' => 'social.png',
                'color_code' => '#96CEB4',
                'sort_order' => 4,
                'is_active' => true
            ],
            [
                'name' => 'Spiritualitas',
                'description' => 'Meningkatkan kedekatan dengan Allah SWT',
                'icon' => 'spirituality.png',
                'color_code' => '#FFEAA7',
                'sort_order' => 5,
                'is_active' => true
            ],
            [
                'name' => 'Motivasi dan Tujuan',
                'description' => 'Menemukan motivasi dan tujuan hidup yang bermakna',
                'icon' => 'motivation.png',
                'color_code' => '#DDA0DD',
                'sort_order' => 6,
                'is_active' => true
            ]
        ];

        foreach ($categories as $categoryData) {
            $category = PsychologyCategory::create($categoryData);
            $this->createMaterialsForCategory($category);
        }
    }

    private function createMaterialsForCategory(PsychologyCategory $category)
    {
        $materials = [];

        switch ($category->name) {
            case 'Manajemen Emosi':
                $materials = [
                    [
                        'title' => 'Mengelola Amarah dalam Perspektif Islam',
                        'summary' => 'Bagaimana Islam mengajarkan kita untuk mengendalikan amarah dan mengubahnya menjadi kekuatan positif.',
                        'content' => $this->getAngerManagementContent(),
                        'author' => 'Dr. Ahmad Muhajir',
                        'source' => 'Quranic Psychology Institute',
                        'tags' => json_encode(['amarah', 'emosi', 'sabar', 'self-control']),
                        'difficulty_level' => 'beginner',
                        'estimated_read_time' => 8,
                        'is_featured' => true,
                        'is_published' => true,
                        'published_at' => Carbon::now()->subDays(10)
                    ],
                    [
                        'title' => 'Transformasi Emosi Negatif Menjadi Positif',
                        'summary' => 'Teknik-teknik islami untuk mengubah emosi negatif menjadi energi positif dalam kehidupan sehari-hari.',
                        'content' => $this->getEmotionTransformationContent(),
                        'author' => 'Prof. Siti Nurhaliza',
                        'source' => 'Islamic Psychology Journal',
                        'tags' => json_encode(['emosi negatif', 'transformasi', 'positif', 'dzikir']),
                        'difficulty_level' => 'intermediate',
                        'estimated_read_time' => 12,
                        'is_featured' => false,
                        'is_published' => true,
                        'published_at' => Carbon::now()->subDays(5)
                    ]
                ];
                break;

            case 'Stres dan Kecemasan':
                $materials = [
                    [
                        'title' => 'Mengatasi Kecemasan dengan Dzikir dan Doa',
                        'summary' => 'Pendekatan spiritual untuk mengatasi kecemasan melalui dzikir, doa, dan tawakkal kepada Allah.',
                        'content' => $this->getAnxietyManagementContent(),
                        'author' => 'Dr. Muhammad Ridwan',
                        'source' => 'Islamic Mental Health Center',
                        'tags' => json_encode(['kecemasan', 'dzikir', 'doa', 'tawakkal']),
                        'difficulty_level' => 'beginner',
                        'estimated_read_time' => 10,
                        'is_featured' => true,
                        'is_published' => true,
                        'published_at' => Carbon::now()->subDays(7)
                    ],
                    [
                        'title' => 'Stres dalam Kehidupan Modern: Solusi Islami',
                        'summary' => 'Bagaimana menghadapi tekanan hidup modern dengan tetap berpegang pada nilai-nilai Islam.',
                        'content' => $this->getModernStressContent(),
                        'author' => 'Dr. Fatimah Al-Zahra',
                        'source' => 'Contemporary Islamic Psychology',
                        'tags' => json_encode(['stres', 'modern', 'islam', 'solusi']),
                        'difficulty_level' => 'intermediate',
                        'estimated_read_time' => 15,
                        'is_featured' => false,
                        'is_published' => true,
                        'published_at' => Carbon::now()->subDays(3)
                    ]
                ];
                break;

            case 'Kebahagiaan Islami':
                $materials = [
                    [
                        'title' => 'Menemukan Kebahagiaan Sejati dalam Islam',
                        'summary' => 'Konsep kebahagiaan dalam Islam dan cara mencapainya melalui ibadah dan amal saleh.',
                        'content' => $this->getIslamicHappinessContent(),
                        'author' => 'Ustadz Abdullah Hasan',
                        'source' => 'Happiness in Islam Foundation',
                        'tags' => json_encode(['kebahagiaan', 'islam', 'ibadah', 'amal saleh']),
                        'difficulty_level' => 'beginner',
                        'estimated_read_time' => 9,
                        'is_featured' => true,
                        'is_published' => true,
                        'published_at' => Carbon::now()->subDays(12)
                    ]
                ];
                break;

            case 'Hubungan Sosial':
                $materials = [
                    [
                        'title' => 'Membangun Hubungan yang Harmonis dalam Islam',
                        'summary' => 'Prinsip-prinsip Islam dalam membangun hubungan yang baik dengan keluarga, teman, dan masyarakat.',
                        'content' => $this->getSocialRelationshipContent(),
                        'author' => 'Dr. Aminah Wadud',
                        'source' => 'Islamic Social Psychology Research',
                        'tags' => json_encode(['hubungan sosial', 'harmonis', 'keluarga', 'masyarakat']),
                        'difficulty_level' => 'intermediate',
                        'estimated_read_time' => 11,
                        'is_featured' => false,
                        'is_published' => true,
                        'published_at' => Carbon::now()->subDays(8)
                    ]
                ];
                break;

            case 'Spiritualitas':
                $materials = [
                    [
                        'title' => 'Meningkatkan Kedekatan dengan Allah SWT',
                        'summary' => 'Langkah-langkah praktis untuk meningkatkan kualitas spiritual dan kedekatan dengan Allah.',
                        'content' => $this->getSpiritualityContent(),
                        'author' => 'Imam Dr. Yusuf Al-Qardhawi',
                        'source' => 'Islamic Spirituality Institute',
                        'tags' => json_encode(['spiritualitas', 'kedekatan allah', 'ibadah', 'muraqabah']),
                        'difficulty_level' => 'advanced',
                        'estimated_read_time' => 18,
                        'is_featured' => true,
                        'is_published' => true,
                        'published_at' => Carbon::now()->subDays(15)
                    ]
                ];
                break;

            case 'Motivasi dan Tujuan':
                $materials = [
                    [
                        'title' => 'Menemukan Tujuan Hidup dalam Islam',
                        'summary' => 'Bagaimana Islam membantu kita menemukan makna dan tujuan hidup yang sejati.',
                        'content' => $this->getLifePurposeContent(),
                        'author' => 'Dr. Tariq Ramadan',
                        'source' => 'Islamic Life Purpose Academy',
                        'tags' => json_encode(['tujuan hidup', 'makna', 'motivasi', 'visi']),
                        'difficulty_level' => 'intermediate',
                        'estimated_read_time' => 13,
                        'is_featured' => false,
                        'is_published' => true,
                        'published_at' => Carbon::now()->subDays(6)
                    ]
                ];
                break;
        }

        foreach ($materials as $materialData) {
            PsychologyMaterial::create([
                'psychology_category_id' => $category->id,
                ...$materialData
            ]);
        }
    }

    private function getAngerManagementContent()
    {
        return "# Mengelola Amarah dalam Perspektif Islam

## Pendahuluan
Amarah adalah emosi alami yang dimiliki setiap manusia. Dalam Islam, amarah tidak selalu dianggap sebagai sesuatu yang negatif, namun cara kita mengelolanya yang menentukan apakah itu menjadi berkah atau bencana.

## Definisi Amarah dalam Islam
Rasulullah SAW bersabda: \"Bukanlah orang yang kuat itu yang dapat mengalahkan orang lain, tetapi orang yang kuat itu adalah yang dapat mengendalikan dirinya ketika marah.\" (HR. Bukhari)

## Strategi Mengelola Amarah

### 1. Mengambil Wudhu
Ketika merasa marah, segera ambil wudhu. Air wudhu memiliki efek menenangkan secara psikologis dan spiritual.

### 2. Mengubah Posisi
- Jika berdiri, duduklah
- Jika duduk, berbaringlah
- Ini membantu mengurangi intensitas amarah

### 3. Membaca Ta'awudz
\"A'udzu billahi min ash-shaytani'r-rajim\" (Aku berlindung kepada Allah dari setan yang terkutuk)

### 4. Berdiam Diri
Rasulullah SAW bersabda: \"Jika salah seorang dari kalian marah, hendaklah ia diam.\" (HR. Ahmad)

## Manfaat Mengendalikan Amarah
1. Mendapat ridha Allah SWT
2. Hubungan sosial yang lebih baik
3. Kesehatan mental dan fisik yang optimal
4. Menjadi teladan bagi orang lain

## Kesimpulan
Mengendalikan amarah adalah tanda kekuatan jiwa dan kedewasaan spiritual. Dengan mengikuti tuntunan Rasulullah SAW, kita dapat mengubah amarah menjadi kekuatan positif.";
    }

    private function getEmotionTransformationContent()
    {
        return "# Transformasi Emosi Negatif Menjadi Positif

## Konsep Dasar
Islam mengajarkan bahwa setiap emosi memiliki hikmah dan dapat ditransformasi menjadi energi positif melalui pendekatan spiritual yang tepat.

## Teknik Transformasi

### 1. Kesedihan → Kedekatan dengan Allah
- Gunakan momen sedih untuk lebih banyak berdoa
- Bacalah Al-Quran untuk mendapatkan ketenangan
- Ingatlah bahwa Allah selalu bersama hamba-Nya yang sabar

### 2. Takut → Tawakkal
- Ubah rasa takut menjadi tawakkal kepada Allah
- Yakinlah bahwa segala sesuatu terjadi atas izin Allah
- Lakukan ikhtiar maksimal lalu serahkan hasilnya kepada Allah

### 3. Marah → Hikmah dan Pembelajaran
- Gunakan amarah sebagai motivasi untuk berbuat kebaikan
- Jadikan amarah sebagai alarm untuk introspeksi diri
- Channeling amarah untuk memperjuangkan keadilan

## Dzikir untuk Transformasi Emosi
1. **Untuk kesedihan**: \"Hasbunallahu wa ni'mal wakil\"
2. **Untuk kecemasan**: \"La hawla wa la quwwata illa billah\"
3. **Untuk amarah**: \"A'udzu billahi min ash-shaytani'r-rajim\"

## Praktik Harian
- Muhasabah (introspeksi) setiap malam
- Dzikir pagi dan sore
- Shalat tahajud untuk ketenangan jiwa";
    }

    private function getAnxietyManagementContent()
    {
        return "# Mengatasi Kecemasan dengan Dzikir dan Doa

## Kecemasan dalam Perspektif Islam
Kecemasan adalah ujian yang diberikan Allah untuk menguji dan meningkatkan iman kita. Al-Quran memberikan banyak solusi untuk mengatasinya.

## Dzikir Penenang Kecemasan

### 1. Dzikir La Hawla Wa La Quwwata
\"لَا حَوْلَ وَلَا قُوَّةَ إِلَّا بِاللَّهِ\"
Dzikir ini memberikan ketenangan dengan mengingatkan bahwa segala kekuatan hanya milik Allah.

### 2. Shalawat Nabi
Membaca shalawat untuk Nabi Muhammad SAW dapat menenangkan hati dan mendatangkan keberkahan.

### 3. Istighfar
\"أَسْتَغْفِرُ اللَّهَ الْعَظِيمَ\"
Memohon ampun kepada Allah membersihkan hati dari beban dosa yang menyebabkan kecemasan.

## Doa Khusus Anti Cemas
\"اللَّهُمَّ إِنِّي أَعُوذُ بِكَ مِنَ الْهَمِّ وَالْحَزَنِ\"
(Ya Allah, aku berlindung kepada-Mu dari kesusahan dan kesedihan)

## Teknik Praktis
1. **Shalat Hajat** - Ketika merasa cemas
2. **Tadabbur Al-Quran** - Merenungkan ayat-ayat yang menenangkan
3. **Dzikir berulang** - Minimal 100 kali setelah shalat
4. **Tawakkal** - Menyerahkan urusan kepada Allah";
    }

    private function getModernStressContent()
    {
        return "# Stres dalam Kehidupan Modern: Solusi Islami

## Tantangan Zaman Modern
Kehidupan modern membawa tekanan yang kompleks: pekerjaan, teknologi, ekspektasi sosial, dan materialisme yang dapat menyebabkan stres kronis.

## Solusi Islami untuk Stres Modern

### 1. Time Management Islami
- Mulai hari dengan shalat Subuh
- Sisipkan dzikir di antara aktivitas
- Akhiri hari dengan muhasabah

### 2. Digital Detox Islami
- Batasi penggunaan media sosial
- Gunakan waktu luang untuk membaca Al-Quran
- Ganti scrolling dengan dzikir

### 3. Work-Life Balance dalam Islam
- Bekerja sebagai ibadah
- Istirahat sebagai hak tubuh
- Prioritaskan keluarga dan ibadah

## Mindset Islami Anti-Stres
1. **Qana'ah** - Merasa cukup dengan apa yang dimiliki
2. **Sabar** - Menghadapi cobaan dengan tenang
3. **Syukur** - Fokus pada nikmat Allah
4. **Tawakkal** - Menyerahkan hasil kepada Allah

## Ritual Harian Anti-Stres
- Shalat 5 waktu sebagai 'time out'
- Dzikir dalam perjalanan
- Doa sebelum tidur
- Bangun malam untuk tahajud";
    }

    private function getIslamicHappinessContent()
    {
        return "# Menemukan Kebahagiaan Sejati dalam Islam

## Definisi Kebahagiaan Islami
Kebahagiaan dalam Islam bukan sekadar kesenangan duniawi, tetapi kebahagiaan yang menyeluruh: dunia dan akhirat, lahir dan batin.

## Sumber Kebahagiaan dalam Islam

### 1. Hubungan dengan Allah (Hablumminallah)
- Shalat yang khusyuk
- Dzikir dan doa
- Membaca dan memahami Al-Quran
- Mendekatkan diri kepada Allah

### 2. Hubungan dengan Sesama (Hablumminannas)
- Berbuat baik kepada orang tua
- Menjaga silaturahmi
- Membantu sesama
- Memaafkan dan meminta maaf

### 3. Hubungan dengan Diri Sendiri
- Menerima diri apa adanya
- Terus berusaha memperbaiki diri
- Bersyukur atas nikmat Allah
- Sabar dalam menghadapi cobaan

## Formula Kebahagiaan Islami
**Iman + Amal Saleh = Kebahagiaan Dunia Akhirat**

### Indikator Kebahagiaan Islami
1. Hati yang tenang (sakinah)
2. Wajah yang berseri (nur)
3. Mudah dalam urusan (barakah)
4. Dicintai Allah dan makhluk-Nya

## Praktik Harian Kebahagiaan
- Bangun dengan bersyukur
- Shalat dengan khusyuk
- Berbagi kebaikan
- Tidur dengan istighfar";
    }

    private function getSocialRelationshipContent()
    {
        return "# Membangun Hubungan yang Harmonis dalam Islam

## Prinsip Dasar Hubungan Islami
Islam mengajarkan bahwa manusia adalah makhluk sosial yang membutuhkan interaksi harmonis untuk mencapai kebahagiaan dunia dan akhirat.

## Akhlak dalam Berhubungan

### 1. Dengan Keluarga
- **Birrul walidain** - Berbakti kepada orang tua
- **Kasih sayang** kepada pasangan dan anak
- **Silaturahmi** dengan saudara

### 2. Dengan Tetangga
- Saling menghormati dan membantu
- Tidak mengganggu ketenangan
- Berbagi dalam kebahagiaan dan kesusahan

### 3. Dengan Masyarakat
- **Fastabiqul khairat** - Berlomba dalam kebaikan
- **Amar ma'ruf nahi munkar**
- Menjadi teladan yang baik

## Etika Komunikasi Islami
1. **Qaulan sadida** - Perkataan yang benar
2. **Qaulan ma'rufa** - Perkataan yang baik
3. **Qaulan layyina** - Perkataan yang lemah lembut
4. **Qaulan karima** - Perkataan yang mulia

## Mengatasi Konflik Hubungan
- **Ishlah** - Perdamaian dan perbaikan
- **Maghfirah** - Saling memaafkan
- **Sabr** - Sabar dalam menghadapi perbedaan
- **Hikmah** - Bijaksana dalam bertindak";
    }

    private function getSpiritualityContent()
    {
        return "# Meningkatkan Kedekatan dengan Allah SWT

## Makna Kedekatan dengan Allah
Kedekatan dengan Allah (qurb) adalah tujuan tertinggi seorang Muslim, di mana hati menjadi tenang dan hidup menjadi bermakna.

## Tingkatan Spiritualitas

### 1. Islam - Penyerahan
Melaksanakan rukun Islam dengan baik dan benar

### 2. Iman - Keyakinan
Memperkuat keyakinan melalui ilmu dan amal

### 3. Ihsan - Kesempurnaan
Beribadah seolah-olah melihat Allah

## Praktik Peningkatan Spiritualitas

### A. Ibadah Wajib
- Shalat dengan khusyuk dan tepat waktu
- Zakat dengan ikhlas
- Puasa dengan makna
- Haji dengan kekhidmatan

### B. Ibadah Sunnah
- **Shalat Tahajud** - Berkomunikasi dengan Allah di sepertiga malam
- **Puasa Sunnah** - Mendekatkan diri melalui pengendalian diri
- **Dzikir dan Wirid** - Mengingat Allah setiap saat
- **Tilawah Al-Quran** - Mendengarkan firman Allah

### C. Muraqabah (Kontemplasi)
- Merenungkan ciptaan Allah
- Introspeksi diri (muhasabah)
- Kontemplasi tentang kematian
- Meditasi dalam dzikir

## Tanda-tanda Kedekatan dengan Allah
1. Hati yang tenang dalam segala keadaan
2. Mudah dalam beribadah
3. Terhindar dari maksiat
4. Dicintai oleh makhluk Allah
5. Doa yang mustajab
6. Hidup yang penuh berkah

## Tahapan Perjalanan Spiritual
1. **Taubat** - Kembali kepada Allah
2. **Zuhud** - Mengurangi cinta dunia
3. **Tawakkal** - Bergantung sepenuhnya kepada Allah
4. **Ridha** - Rela dengan ketentuan Allah
5. **Mahabbah** - Cinta kepada Allah
6. **Ma'rifah** - Mengenal Allah dengan hati";
    }

    private function getLifePurposeContent()
    {
        return "# Menemukan Tujuan Hidup dalam Islam

## Tujuan Penciptaan Manusia
\"Dan Aku tidak menciptakan jin dan manusia melainkan agar mereka beribadah kepada-Ku.\" (QS. Az-Zariyat: 56)

## Dimensi Tujuan Hidup Muslim

### 1. Spiritual - Mengenal dan Mengabdi kepada Allah
- Memperkuat iman dan takwa
- Meningkatkan kualitas ibadah
- Mendekatkan diri kepada Allah

### 2. Personal - Mengembangkan Potensi Diri
- Menuntut ilmu sepanjang hayat
- Mengembangkan bakat dan kemampuan
- Menjadi pribadi yang bermanfaat

### 3. Sosial - Berkontribusi untuk Umat
- Menjadi khalifah di bumi
- Menyebarkan kebaikan
- Membangun peradaban yang adil

### 4. Universal - Menjadi Rahmat bagi Semesta
- Melestarikan lingkungan
- Menegakkan keadilan
- Menyebarkan kedamaian

## Langkah Menemukan Tujuan Hidup

### 1. Muhasabah (Introspeksi)
- Renungkan makna hidup
- Evaluasi perjalanan hidup
- Identifikasi nilai-nilai yang dipegang

### 2. Doa dan Istikharah
- Memohon petunjuk Allah
- Shalat istikharah untuk keputusan besar
- Berdzikir untuk kejernihan hati

### 3. Mengenal Diri
- Kenali kelebihan dan kekurangan
- Temukan passion dan bakat
- Pahami peran dalam masyarakat

### 4. Belajar dari Teladan
- Meneladani Rasulullah SAW
- Belajar dari para salaf
- Mengambil hikmah dari orang-orang sukses

## Indikator Hidup yang Bermakna
1. Merasa damai dengan pilihan hidup
2. Berkontribusi positif bagi orang lain
3. Terus berkembang dan belajar
4. Siap menghadapi kematian dengan tenang
5. Meninggalkan warisan kebaikan";
    }
}
