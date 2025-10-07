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
        Schema::create('psychology_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychology_category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('content'); // Main article content
            $table->string('author')->nullable();
            $table->string('source')->nullable(); // Reference/source
            $table->string('featured_image')->nullable();
            $table->json('tags')->nullable(); // Related topics/emotions
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('estimated_read_time')->nullable(); // in minutes
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index(['psychology_category_id', 'is_published']);
            $table->index(['is_featured', 'published_at']);
            // Fulltext index removed - can be added separately if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psychology_materials');
    }
};
