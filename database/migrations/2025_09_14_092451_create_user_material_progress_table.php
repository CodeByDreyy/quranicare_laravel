
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
        Schema::create('user_material_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('psychology_material_id')->constrained()->onDelete('cascade');
            $table->decimal('progress_percentage', 5, 2)->default(0); // 0-100%
            $table->integer('time_spent_seconds')->default(0);
            $table->boolean('completed')->default(false);
            $table->boolean('bookmarked')->default(false);
            $table->integer('rating')->nullable(); // 1-5 stars
            $table->text('notes')->nullable(); // User's personal notes
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['user_id', 'psychology_material_id']);
            $table->index(['user_id', 'completed']);
            $table->index(['user_id', 'bookmarked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_material_progress');
    }
};
