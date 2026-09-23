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
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('program_code', 10);
            $table->string('program_name');
            $table->decimal('confidence', 5, 2)->default(0);
            $table->string('fit_level')->nullable();
            $table->string('source', 20)->default('ollama');
            $table->string('strongest_trait')->nullable();
            $table->decimal('strongest_trait_percentage', 5, 2)->default(0);
            $table->json('result')->nullable();
            $table->json('category_summary')->nullable();
            $table->timestamp('completed_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};
