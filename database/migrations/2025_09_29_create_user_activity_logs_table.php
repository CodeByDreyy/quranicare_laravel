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
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('activity_type', [
                'quran_reading',
                'dzikir_session', 
                'breathing_exercise',
                'audio_relaxation',
                'journal_writing',
                'qalbu_chat',
                'psychology_material',
                'app_open',
                'mood_tracking'
            ]);
            $table->string('activity_title')->nullable(); // e.g., "Surah Al-Fatihah", "Dzikir Pagi"
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of the specific record (surah_id, dzikir_id, etc.)
            $table->string('reference_table')->nullable(); // Which table the reference_id points to
            $table->integer('duration_seconds')->nullable(); // How long the activity took
            $table->decimal('completion_percentage', 5, 2)->default(0); // 0-100%
            $table->json('metadata')->nullable(); // Additional data (mood, notes, etc.)
            $table->date('activity_date'); // Date of the activity (for daily grouping)
            $table->time('activity_time'); // Time of the activity
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['user_id', 'activity_date']);
            $table->index(['user_id', 'activity_type']);
            $table->index(['user_id', 'activity_date', 'activity_type']);
            $table->index(['reference_table', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};