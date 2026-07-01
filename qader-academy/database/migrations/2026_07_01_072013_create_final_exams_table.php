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
        Schema::create('final_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title_en')->nullable();
            $table->string('title_ar')->nullable();
            $table->text('instructions_en')->nullable();
            $table->text('instructions_ar')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->integer('passing_score')->default(50); // percentage
            $table->boolean('is_available')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_exams');
    }
};
