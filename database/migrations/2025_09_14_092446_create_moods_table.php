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
        Schema::create('moods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('mood_type', ['senang', 'sedih', 'biasa_saja', 'marah', 'murung']);
            $table->text('notes')->nullable();
            $table->date('mood_date');
            $table->time('mood_time');
            $table->timestamps();
            
            $table->index(['user_id', 'mood_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moods');
    }
};
