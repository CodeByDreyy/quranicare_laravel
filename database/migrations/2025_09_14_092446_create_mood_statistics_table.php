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
        Schema::create('mood_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->json('mood_counts'); // {"senang": 2, "sedih": 1, "biasa_saja": 3, "marah": 0, "murung": 1}
            $table->enum('dominant_mood', ['senang', 'sedih', 'biasa_saja', 'marah', 'murung'])->nullable();
            $table->decimal('mood_score', 3, 2)->nullable(); // Overall mood score for the day
            $table->integer('total_entries');
            $table->text('notes')->nullable(); // Daily reflection
            $table->timestamps();
            
            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'date']);
            $table->index('mood_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mood_statistics');
    }
};
