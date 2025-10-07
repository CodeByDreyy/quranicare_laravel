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
        Schema::create('qalbu_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable(); // Auto-generated or user-set
            $table->enum('conversation_type', ['general', 'mood_support', 'spiritual_guidance', 'quran_guidance'])->default('general');
            $table->enum('user_emotion', ['senang', 'sedih', 'biasa_saja', 'marah', 'murung', 'bingung', 'takut', 'cemas'])->nullable();
            $table->json('context_data')->nullable(); // Additional context like current mood, recent activities
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_message_at');
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qalbu_conversations');
    }
};
