<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('unanswered_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('qalbu_conversation_id')->nullable();
            $table->text('question');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('qalbu_conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unanswered_questions');
    }
};


