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
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->string('question_en');
            $table->string('question_ar');
            $table->enum('type', ['mcq', 'written'])->default('mcq');
            $table->json('options')->nullable(); // for MCQ: [{text_en, text_ar, is_correct}, ...]
            $table->text('correct_answer_en')->nullable(); // for written questions
            $table->text('correct_answer_ar')->nullable();
            $table->integer('points')->default(1);
            $table->timestamps();
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
