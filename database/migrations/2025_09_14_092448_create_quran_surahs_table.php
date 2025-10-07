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
        Schema::create('quran_surahs', function (Blueprint $table) {
            $table->id();
            $table->integer('number'); // Surah number 1-114
            $table->string('name_arabic');
            $table->string('name_indonesian');
            $table->string('name_english');
            $table->string('name_latin');
            $table->integer('number_of_ayahs');
            $table->enum('place', ['Meccan', 'Medinan']);
            $table->text('description_indonesian')->nullable();
            $table->text('description_english')->nullable();
            $table->string('audio_url')->nullable(); // Full surah audio
            $table->timestamps();
            
            $table->unique('number');
            $table->index('place');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_surahs');
    }
};
