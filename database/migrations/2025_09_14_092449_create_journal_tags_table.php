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
        Schema::create('journal_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color_code', 7)->nullable(); // hex color
            $table->text('description')->nullable();
            $table->integer('usage_count')->default(0);
            $table->boolean('is_system_tag')->default(false); // For pre-defined tags
            $table->timestamps();
            
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_tags');
    }
};
