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
        Schema::create('final_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_exam_id')->constrained()->onDelete('cascade');
            $table->string('question_en');
            $table->string('question_ar');
            $table->enum('type', ['mcq', 'written'])->default('mcq');
            $table->json('options')->nullable(); // for MCQ: [{text_en, text_ar, is_correct}, ...]
            $table->text('correct_answer_en')->nullable();
            $table->text('correct_answer_ar')->nullable();
            $table->integer('points')->default(1);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_exam_questions');
    }
};
