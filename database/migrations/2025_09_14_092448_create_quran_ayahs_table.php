<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quran_ayahs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quran_surah_id'); // Reference without foreign key
            $table->integer('number'); // Ayah number within surah
            $table->text('text_arabic');
            $table->text('text_indonesian');
            $table->text('text_english')->nullable();
            $table->text('text_latin')->nullable(); // Transliteration
            $table->text('tafsir_indonesian')->nullable();
            $table->text('tafsir_english')->nullable();
            $table->string('audio_url')->nullable(); // Individual ayah audio
            $table->json('keywords')->nullable(); // For search and AI purposes
            $table->timestamps();
            
            $table->unique(['quran_surah_id', 'number']);
            $table->index(['quran_surah_id', 'number']);
            // Fulltext index removed - can be added separately if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_ayahs');
    }
};
