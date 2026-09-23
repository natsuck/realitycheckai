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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('source_file');
            $table->string('original_filename')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->enum('status', ['queued', 'processing', 'ready', 'failed'])->default('queued')->index();
            $table->string('ai_model')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->text('generation_error')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
