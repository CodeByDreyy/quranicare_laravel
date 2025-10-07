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
        // Drop existing tables to recreate with API structure
        Schema::dropIfExists('user_dzikir_sessions');
        Schema::dropIfExists('dzikir_doa');
        Schema::dropIfExists('dzikir_categories');

        // Create new structure based on equran.id API
        Schema::create('doa_dzikir', function (Blueprint $table) {
            $table->id();
            $table->string('grup'); // Group/category like "Dzikir Pagi", "Beberapa Adab Dan Keutamaan"
            $table->string('nama'); // Name of the doa/dzikir
            $table->text('ar'); // Arabic text
            $table->text('tr'); // Transliteration
            $table->text('idn'); // Indonesian translation
            $table->longText('tentang'); // About/description/source
            $table->json('tag')->nullable(); // Tags array from API
            $table->integer('api_id')->unique(); // Original ID from equran.id API
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            
            $table->index('grup');
            $table->index('is_active');
            $table->index('api_id');
            $table->fullText(['nama', 'ar', 'tr', 'idn', 'tentang']);
        });

        // Create user sessions table for the new structure
        Schema::create('user_doa_dzikir_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('doa_dzikir_id')->constrained('doa_dzikir')->onDelete('cascade');
            $table->integer('completed_count')->default(0);
            $table->integer('target_count')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->enum('mood_before', ['senang', 'sedih', 'biasa_saja', 'marah', 'murung'])->nullable();
            $table->enum('mood_after', ['senang', 'sedih', 'biasa_saja', 'marah', 'murung', 'tenang', 'bersyukur'])->nullable();
            $table->text('notes')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'doa_dzikir_id']);
            $table->index(['user_id', 'created_at']);
        });

        // Update user_favorites table to use new structure
        Schema::table('user_favorites', function (Blueprint $table) {
            // Update enum to include new doa_dzikir type
            $table->dropIndex(['favoritable_type', 'favoritable_id']);
            $table->dropIndex(['user_id', 'favoritable_type']);
        });

        Schema::table('user_favorites', function (Blueprint $table) {
            // Recreate indexes after enum change
            $table->index(['favoritable_type', 'favoritable_id']);
            $table->index(['user_id', 'favoritable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_doa_dzikir_sessions');
        Schema::dropIfExists('doa_dzikir');
        
        // Recreate original structure
        Schema::create('dzikir_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color_code', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dzikir_doa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dzikir_category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('arabic_text');
            $table->text('latin_text')->nullable();
            $table->text('indonesian_translation');
            $table->text('benefits')->nullable();
            $table->text('context')->nullable();
            $table->string('source')->nullable();
            $table->string('audio_path')->nullable();
            $table->integer('repeat_count')->nullable();
            $table->json('emotional_tags')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['dzikir_category_id', 'is_active']);
        });

        Schema::create('user_dzikir_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('dzikir_doa_id')->constrained('dzikir_doa')->onDelete('cascade');
            $table->integer('completed_count')->default(0);
            $table->integer('target_count')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->enum('mood_before', ['senang', 'sedih', 'biasa_saja', 'marah', 'murung'])->nullable();
            $table->enum('mood_after', ['senang', 'sedih', 'biasa_saja', 'marah', 'murung', 'tenang', 'bersyukur'])->nullable();
            $table->text('notes')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'dzikir_doa_id']);
            $table->index(['user_id', 'created_at']);
        });
    }
};