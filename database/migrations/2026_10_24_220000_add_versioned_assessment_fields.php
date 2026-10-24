<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('instrument_version')->nullable()->index();
            $table->unsignedSmallInteger('item_number')->nullable();
            $table->string('section')->nullable();
            $table->string('question_type')->default('likert');
            $table->string('riasec_type', 1)->nullable();
            $table->json('choices')->nullable();
            $table->unsignedTinyInteger('correct_answer')->nullable();
        });

        Schema::table('assessment_results', function (Blueprint $table) {
            $table->string('instrument_version')->nullable()->index();
            $table->json('score_breakdown')->nullable();
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->foreignId('assessment_result_id')
                ->nullable()
                ->after('question_id')
                ->constrained('assessment_results')
                ->nullOnDelete();
            $table->boolean('is_correct')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_result_id');
            $table->dropColumn('is_correct');
        });

        Schema::table('assessment_results', function (Blueprint $table) {
            $table->dropColumn(['instrument_version', 'score_breakdown']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn([
                'instrument_version',
                'item_number',
                'section',
                'question_type',
                'riasec_type',
                'choices',
                'correct_answer',
            ]);
        });
    }
};
