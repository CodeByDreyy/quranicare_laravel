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
        Schema::create('audio_listening_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('audio_relax_id')->constrained('audio_relax')->onDelete('cascade');
            $table->integer('listened_duration_seconds'); // How long user listened
            $table->boolean('completed')->default(false); // If user listened to the end
            $table->decimal('progress_percentage', 5, 2)->default(0); // 0-100%
            $table->integer('rating')->nullable(); // User rating 1-5
            $table->text('review')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'audio_relax_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_listening_sessions');
    }
};
