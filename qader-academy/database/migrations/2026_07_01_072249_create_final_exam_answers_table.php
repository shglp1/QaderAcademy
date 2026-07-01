<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_exam_attempt_id')->constrained('final_exam_attempts')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('final_exam_questions')->onDelete('cascade');
            $table->text('student_answer');
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_exam_answers');
    }
};
