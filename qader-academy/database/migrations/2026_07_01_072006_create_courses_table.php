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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('motivation_goal_en')->nullable();
            $table->text('motivation_goal_ar')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->enum('status', ['draft', 'pending', 'published', 'rejected'])->default('draft');
            $table->json('rejection_reason')->nullable(); // for admin feedback in both languages
            $table->boolean('requires_sequential_completion')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
