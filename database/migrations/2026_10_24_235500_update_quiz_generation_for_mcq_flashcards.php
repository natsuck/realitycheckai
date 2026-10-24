<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('generated_at');
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->text('explanation')->nullable()->after('correct_answer');
            $table->dropIndex(['difficulty']);
            $table->dropColumn('difficulty');
        });

        Schema::create('quiz_flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('front');
            $table->text('back');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_flashcards');

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium')->index();
            $table->dropColumn('explanation');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
        });
    }
};
