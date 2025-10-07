<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiKnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $knowledgeData = [
            // Greetings & Basic Interactions
            [
                'content_type' => 'general_guidance',
                'content_id' => null,
                'emotion_trigger' => 'neutral',
                'context_keywords' => 'assalamualaikum,halo,hai,selamat,kabar,apa,bagaimana,greeting',
                'guidance_text' => 'Assalamualaikum warahmatullahi wabarakatuh! Alhamdulillahi rabbil alamiin. Selamat datang di QuraniCare, sahabat. Saya di sini untuk menemani dan membantu Anda dengan nasihat Islami. Bagaimana kabar Anda hari ini?',
                'suggested_actions' => json_encode(['greeting_response']),
                'effectiveness_score' => 0.9,
                'is_active' => true,
            ],
            [
                'content_type' => 'general_guidance',
                'content_id' => null,
                'emotion_trigger' => 'neutral',
                'context_keywords' => 'selamat,tinggal,bye,wassalam,pamit,pergi,farewell',
                'guidance_text' => 'Barakallahu fiikum. Semoga Allah senantiasa melindungi dan memberkahi Anda. Wassalamualaikum warahmatullahi wabarakatuh. Jangan ragu untuk kembali kapan saja jika butuh teman bicara. 🤲',
                'suggested_actions' => json_encode(['closing_prayer']),
                'effectiveness_score' => 0.8,
                'is_active' => true,
            ],

            // Emotional Support - Sadness
            [
                'content_type' => 'quran_ayah',
                'content_id' => null,
                'emotion_trigger' => 'sad',
                'context_keywords' => 'sedih,galau,down,kecewa,patah,hati,hancur,terpuruk,menangis',
                'guidance_text' => 'Allah berfirman dalam Al-Quran: "Dan barangsiapa bertawakal kepada Allah, maka Allah akan mencukupkan (keperluan)nya. Sesungguhnya Allah melaksanakan urusan-Nya." (QS. At-Talaq: 3). Kesedihan adalah ujian yang Allah berikan untuk menguatkan kita. Ingatlah bahwa setelah kesulitan pasti ada kemudahan.',
                'suggested_actions' => json_encode(['dzikir_istigfar', 'doa_lapang_dada']),
                'effectiveness_score' => 0.9,
                'is_active' => true,
            ],
            [
                'content_type' => 'psychology_material',
                'content_id' => null,
                'emotion_trigger' => 'anxious',
                'context_keywords' => 'cemas,khawatir,takut,gelisah,stress,panik,overthinking,nervous',
                'guidance_text' => 'Rasulullah ﷺ bersabda: "Barangsiapa yang memperbanyak istighfar, maka Allah akan memberikan jalan keluar dari setiap kesulitan, dan memberikan rezeki dari arah yang tidak disangka-sangka." (HR. Abu Dawud). Coba perbanyak istighfar dan dzikir untuk menenangkan hati.',
                'suggested_actions' => json_encode(['istighfar_100x', 'breathing_exercise']),
                'effectiveness_score' => 0.8,
                'is_active' => true,
            ],

            // Prayer & Worship
            [
                'content_type' => 'quran_ayah',
                'content_id' => null,
                'emotion_trigger' => 'unmotivated',
                'context_keywords' => 'sholat,solat,prayer,ibadah,malas,males,sulit,susah,worship',
                'guidance_text' => 'Allah berfirman: "Dan tegakkanlah sholat. Sesungguhnya sholat itu mencegah dari (perbuatan) keji dan mungkar." (QS. Al-Ankabut: 45). Sholat adalah tiang agama dan obat untuk hati yang resah. Mulailah dengan sholat wajib yang khusyuk.',
                'suggested_actions' => json_encode(['reminder_sholat', 'doa_before_prayer']),
                'effectiveness_score' => 0.9,
                'is_active' => true,
            ],

            // Relationships & Family
            [
                'content_type' => 'quran_ayah',
                'content_id' => null,
                'emotion_trigger' => 'frustrated',
                'context_keywords' => 'keluarga,orang,tua,ayah,ibu,bapak,mama,papa,konflik,bertengkar,family',
                'guidance_text' => 'Allah berfirman: "Dan rendahkanlah dirimu terhadap mereka berdua (orang tua) dengan penuh kasih sayang dan ucapkanlah: Ya Tuhanku, kasihilah mereka keduanya, sebagaimana mereka berdua telah mendidik aku waktu kecil." (QS. Al-Isra: 24). Bersabarlah dengan keluarga, berbuat baiklah meski sulit.',
                'suggested_actions' => json_encode(['doa_untuk_orangtua', 'patience_exercise']),
                'effectiveness_score' => 0.8,
                'is_active' => true,
            ],

            // Forgiveness & Repentance
            [
                'content_type' => 'quran_ayah',
                'content_id' => null,
                'emotion_trigger' => 'guilty',
                'context_keywords' => 'dosa,salah,tobat,ampun,menyesal,bersalah,jahat,sin,repent',
                'guidance_text' => 'Allah berfirman: "Katakanlah: Hai hamba-hamba-Ku yang melampaui batas terhadap diri mereka sendiri, janganlah berputus asa dari rahmat Allah. Sesungguhnya Allah mengampuni dosa-dosa semuanya." (QS. Az-Zumar: 53). Allah Maha Pengampun, tidak ada dosa yang terlalu besar untuk diampuni-Nya.',
                'suggested_actions' => json_encode(['istighfar_taubat', 'doa_ampunan']),
                'effectiveness_score' => 0.9,
                'is_active' => true,
            ],

            // Gratitude & Happiness
            [
                'content_type' => 'quran_ayah',
                'content_id' => null,
                'emotion_trigger' => 'happy',
                'context_keywords' => 'syukur,bahagia,senang,gembira,nikmat,rezeki,berkah,grateful',
                'guidance_text' => 'Allah berfirman: "Dan jika kamu bersyukur, pasti Kami akan menambah (nikmat) kepadamu." (QS. Ibrahim: 7). Alhamdulillah, bersyukur adalah kunci kebahagiaan. Hitunglah nikmat Allah yang tak terhingga dalam hidup Anda.',
                'suggested_actions' => json_encode(['dzikir_syukur', 'counting_blessings']),
                'effectiveness_score' => 0.9,
                'is_active' => true,
            ],

            // Patience & Trials
            [
                'content_type' => 'quran_ayah',
                'content_id' => null,
                'emotion_trigger' => 'struggling',
                'context_keywords' => 'sabar,ujian,cobaan,musibah,sulit,susah,berat,lelah,trial,test',
                'guidance_text' => 'Allah berfirman: "Dan berikanlah kabar gembira kepada orang-orang yang sabar, (yaitu) orang-orang yang apabila ditimpa musibah, mereka mengucapkan: Innā lillāhi wa innā ilayhi rājiūn." (QS. Al-Baqarah: 155-156). Sabar adalah separuh dari iman.',
                'suggested_actions' => json_encode(['doa_sabar', 'dzikir_innalillahi']),
                'effectiveness_score' => 0.8,
                'is_active' => true,
            ],

            // Love & Marriage
            [
                'content_type' => 'quran_ayah',
                'content_id' => null,
                'emotion_trigger' => 'romantic',
                'context_keywords' => 'nikah,menikah,pasangan,suami,istri,cinta,sayang,jodoh,marriage',
                'guidance_text' => 'Allah berfirman: "Dan di antara tanda-tanda kekuasaan-Nya ialah Dia menciptakan untukmu isteri-isteri dari jenismu sendiri, supaya kamu cenderung dan merasa tenteram kepadanya, dan dijadikan-Nya diantaramu rasa kasih dan sayang." (QS. Ar-Rum: 21)',
                'suggested_actions' => json_encode(['doa_jodoh', 'istikhara']),
                'effectiveness_score' => 0.8,
                'is_active' => true,
            ],

            // Work & Career
            [
                'content_type' => 'psychology_material',
                'content_id' => null,
                'emotion_trigger' => 'motivated',
                'context_keywords' => 'kerja,karir,usaha,bisnis,pengangguran,capek,lelah,resign,work,job',
                'guidance_text' => 'Rasulullah ﷺ bersabda: "Allah mencintai hamba yang bekerja dan memiliki keahlian." (HR. Ath-Thabrani). Bekerjalah dengan niat ibadah, ikhtiar terbaik, dan serahkan hasilnya kepada Allah. Rezeki sudah ada yang mengatur.',
                'suggested_actions' => json_encode(['doa_before_work', 'dzikir_rezeki']),
                'effectiveness_score' => 0.7,
                'is_active' => true,
            ],
        ];

        // Insert data
        foreach ($knowledgeData as $data) {
            DB::table('ai_knowledge_base')->insert(array_merge($data, [
                'usage_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}