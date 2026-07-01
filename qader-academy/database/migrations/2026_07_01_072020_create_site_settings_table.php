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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value'); // stores multilingual values: {"en": "...", "ar": "..."}
            $table->string('type')->default('string'); // string, boolean, json, number
            $table->string('group')->nullable(); // e.g., 'general', 'payment', 'certificate'
            $table->boolean('is_public')->default(false); // accessible via API
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
