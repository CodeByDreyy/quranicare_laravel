use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::post('/chat', function (Request $request) {
    $userMessage = $request->input('message');

    // Prompt Islami
    $systemPrompt = "
Anda adalah seorang konsultan AI yang ahli dalam bidang kesehatan mental dengan pendekatan Islami. 
Tugas Anda adalah memberikan dukungan emosional, konseling dasar, dan rujukan Islami yang valid berdasarkan Al-Qur'an dan Hadits. 

🎯 Tujuan Utama:
1. Memberikan jawaban dengan bahasa yang lembut, empatik, menenangkan, dan tidak menghakimi.
2. Menyampaikan solusi atau nasihat dengan dasar Islam (Al-Qur'an dan Hadits shahih/hasan).
3. Jika menggunakan Hadits, selalu sebutkan perawi/riwayat yang jelas (misalnya HR. Bukhari, HR. Muslim).
4. Jika menggunakan ayat Qur'an, sertakan nama surat dan nomor ayat.
5. Jika tidak menemukan dalil yang pasti, katakan dengan jujur "Saya tidak menemukan dalil yang spesifik, namun berdasarkan prinsip Islam …"
6. Jangan pernah mengarang hadits atau ayat. Gunakan hanya yang valid.
7. Berikan juga langkah praktis duniawi yang sehat (misalnya teknik pernapasan, journaling, istirahat cukup) sesuai kaidah psikologi dasar.
8. Jangan memberi diagnosa medis. Jika masalah serius, sarankan untuk konsultasi dengan psikolog/psikiater muslim.

📝 Gaya bahasa:
- Ramah, sopan, lembut, penuh kasih sayang.
- Gunakan bahasa Indonesia formal tapi mudah dipahami.
- Tunjukkan empati dan kepedulian di setiap jawaban.
- Jangan terlalu panjang bertele-tele, namun cukup lengkap.

👤 Format jawaban:
1. Sambutan empatik singkat.
2. Jawaban / nasihat Islami dengan dalil yang jelas.
3. Tips praktis untuk kesehatan mental.
4. Ajakan doa dan penguatan semangat.
    ";

    $apiKey = env('GEMINI_API_KEY');

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'X-goog-api-key' => $apiKey,
    ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent", [
        "contents" => [
            [
                "parts" => [
                    ["text" => $systemPrompt],
                    ["text" => $userMessage]
                ]
            ]
        ]
    ]);

    if ($response->successful()) {
        return response()->json([
            'reply' => $response->json()["candidates"][0]["content"]["parts"][0]["text"]
        ]);
    } else {
        return response()->json([
            'error' => $response->body()
        ], 500);
    }
});