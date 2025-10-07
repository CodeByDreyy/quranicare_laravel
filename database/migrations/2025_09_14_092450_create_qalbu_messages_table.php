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
        Schema::create('qalbu_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qalbu_conversation_id')->constrained()->onDelete('cascade');
            $table->enum('sender', ['user', 'ai']);
            $table->text('message');
            $table->json('ai_sources')->nullable(); // Sources used for AI response (ayahs, dzikir, psychology materials)
            $table->json('suggested_actions')->nullable(); // Suggested dzikir, breathing exercise, etc
            $table->enum('ai_response_type', ['text', 'quran_reference', 'dzikir_suggestion', 'breathing_suggestion', 'psychology_reference'])->nullable();
            $table->boolean('is_helpful')->nullable(); // User feedback on AI response
            $table->text('user_feedback')->nullable();
            $table->timestamps();
            
            $table->index(['qalbu_conversation_id', 'created_at']);
            $table->index(['sender', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qalbu_messages');
    }
};
