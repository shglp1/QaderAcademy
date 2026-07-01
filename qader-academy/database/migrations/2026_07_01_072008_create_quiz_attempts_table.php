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
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->json('answers'); // [{question_id, answer_text, selected_option_id}]
            $table->decimal('score', 5, 2)->default(0);
            $table->integer('max_score')->default(0);
            $table->enum('status', ['pending', 'graded', 'auto_graded'])->default('pending');
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('grader_feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
