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
        Schema::create('user_dzikir_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('dzikir_doa_id')->constrained('dzikir_doa')->onDelete('cascade');
            $table->integer('completed_count')->default(0); // How many times completed
            $table->integer('target_count')->nullable(); // Target repetitions
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_dzikir_sessions');
    }
};
