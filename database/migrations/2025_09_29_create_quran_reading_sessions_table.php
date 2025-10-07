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
        Schema::create('quran_reading_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('quran_surah_id')->constrained('quran_surahs')->onDelete('cascade');
            $table->integer('ayah_from')->nullable(); // Starting ayah number
            $table->integer('ayah_to')->nullable(); // Ending ayah number
            $table->integer('reading_duration_seconds')->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0); // Progress in this surah
            $table->enum('reading_type', ['tilawah', 'tadabbur', 'memorization', 'review'])->default('tilawah');
            $table->enum('mood_before', ['senang', 'sedih', 'biasa_saja', 'marah', 'murung', 'cemas'])->nullable();
            $table->enum('mood_after', ['senang', 'sedih', 'biasa_saja', 'marah', 'murung', 'tenang', 'bersyukur', 'khusyuk'])->nullable();
            $table->text('reflection')->nullable(); // Personal reflection
            $table->json('bookmarked_ayahs')->nullable(); // Array of ayah numbers that user bookmarked
            $table->boolean('completed')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'quran_surah_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'reading_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_reading_sessions');
    }
};