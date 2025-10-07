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
        Schema::create('breathing_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('breathing_category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('dzikir_text'); // Subhanallah, Walhamdulillah, etc
            $table->string('audio_path')->nullable(); // Path to dzikir audio
            $table->integer('inhale_duration')->default(2); // seconds
            $table->integer('hold_duration')->default(3); // seconds
            $table->integer('exhale_duration')->default(3); // seconds
            $table->integer('total_cycle_duration')->default(8); // seconds
            $table->integer('default_repetitions')->default(7); // default repetitions for 1 minute
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('breathing_exercises');
    }
};
