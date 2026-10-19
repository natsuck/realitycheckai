<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('title');
            $table->string('slug')->nullable()->index();
            $table->text('summary')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);

            // College Entrance Exams (CETs)
            $table->string('period')->nullable();
            $table->text('coverage')->nullable();
            $table->text('tips')->nullable();

            // Scholarships & grants
            $table->string('grantor')->nullable();
            $table->text('criteria')->nullable();
            $table->date('opens_at')->nullable();
            $table->date('closes_at')->nullable();

            // Philippine universities directory
            $table->string('short_name', 20)->nullable();
            $table->string('location')->nullable();
            $table->string('institution_type')->nullable();
            $table->json('top_programs')->nullable();
            $table->string('entrance_exam')->nullable();
            $table->string('tuition_range')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_bg', 20)->nullable();

            // SHS strands
            $table->string('emoji', 10)->nullable();
            $table->string('subtitle')->nullable();
            $table->text('alignments')->nullable();

            $table->timestamps();

            $table->index(['type', 'is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
