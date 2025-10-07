<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'fallback' to the ai_response_type enum
        DB::statement("ALTER TABLE qalbu_messages MODIFY COLUMN ai_response_type ENUM('text', 'quran_reference', 'dzikir_suggestion', 'breathing_suggestion', 'psychology_reference', 'fallback') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'fallback' from the ai_response_type enum
        DB::statement("ALTER TABLE qalbu_messages MODIFY COLUMN ai_response_type ENUM('text', 'quran_reference', 'dzikir_suggestion', 'breathing_suggestion', 'psychology_reference') NULL");
    }
};
