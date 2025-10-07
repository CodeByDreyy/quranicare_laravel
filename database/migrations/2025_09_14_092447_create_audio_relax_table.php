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
        Schema::create('audio_relax', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('audio_path'); // Path to audio file
            $table->integer('duration_seconds'); // Audio duration in seconds
            $table->string('thumbnail_path')->nullable(); // Preview image
            $table->string('artist')->nullable(); // Reciter/creator name
            $table->integer('download_count')->default(0);
            $table->integer('play_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0); // Average rating 0-5
            $table->integer('rating_count')->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['audio_category_id', 'is_active']);
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_relax');
    }
};
