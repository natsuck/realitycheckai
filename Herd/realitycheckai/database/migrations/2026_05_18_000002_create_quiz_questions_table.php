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
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['mcq', 'identification'])->index();
            $table->text('question');
            $table->json('choices')->nullable();
            $table->text('correct_answer');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium')->index();
            $table->timestamps();

            $table->index(['quiz_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
