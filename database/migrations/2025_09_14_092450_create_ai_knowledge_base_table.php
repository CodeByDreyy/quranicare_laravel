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
        Schema::create('ai_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->enum('content_type', ['quran_ayah', 'dzikir_doa', 'psychology_material', 'general_guidance']);
            $table->foreignId('content_id')->nullable(); // Reference to specific content
            $table->string('emotion_trigger'); // What emotion this content addresses
            $table->text('context_keywords'); // Keywords for matching user problems
            $table->text('guidance_text'); // AI guidance text
            $table->json('suggested_actions')->nullable(); // Recommended actions
            $table->integer('usage_count')->default(0);
            $table->decimal('effectiveness_score', 3, 2)->default(0); // Based on user feedback
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['emotion_trigger', 'content_type']);
            $table->index('effectiveness_score');
            // Fulltext index removed - can be added separately if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_base');
    }
};
