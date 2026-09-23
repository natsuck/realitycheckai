<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The application now recommends through the OpenAI API. Re-label any
     * rows produced by the previous Ollama integration and make the new
     * source label the column default going forward.
     */
    public function up(): void
    {
        DB::table('assessment_results')
            ->where('source', 'ollama')
            ->update(['source' => 'openai']);

        Schema::table('assessment_results', function (Blueprint $table) {
            $table->string('source', 20)->default('openai')->change();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            $table->string('source', 20)->default('ollama')->change();
        });
    }
};
