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
        Schema::create('breathing_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('breathing_exercise_id')->constrained()->onDelete('cascade');
            $table->integer('planned_duration_minutes'); // User selected duration
            $table->integer('actual_duration_seconds')->nullable(); // Actual completed duration
            $table->integer('completed_cycles')->default(0);
            $table->boolean('completed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('breathing_sessions');
    }
};
