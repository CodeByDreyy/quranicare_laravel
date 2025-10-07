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
        Schema::create('dzikir_doa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dzikir_category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('arabic_text');
            $table->text('latin_text')->nullable();
            $table->text('indonesian_translation');
            $table->text('benefits')->nullable(); // Manfaat dzikir/doa
            $table->text('context')->nullable(); // Kapan dibaca
            $table->string('source')->nullable(); // Sumber hadits/ayat
            $table->string('audio_path')->nullable();
            $table->integer('repeat_count')->nullable(); // Berapa kali dibaca
            $table->json('emotional_tags')->nullable(); // ['ketenangan', 'syukur', 'maaf', etc]
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['dzikir_category_id', 'is_active']);
            // Fulltext index removed - can be added separately if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dzikir_doa');
    }
};
