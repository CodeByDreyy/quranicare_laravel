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
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('favoritable_type', ['quran_ayah', 'dzikir_doa', 'audio_relax', 'psychology_material', 'breathing_exercise']);
            $table->unsignedBigInteger('favoritable_id');
            $table->text('notes')->nullable(); // Personal notes about why they favorited
            $table->timestamps();
            
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
            $table->index(['user_id', 'favoritable_type']);
            $table->index(['favoritable_type', 'favoritable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
    }
};
