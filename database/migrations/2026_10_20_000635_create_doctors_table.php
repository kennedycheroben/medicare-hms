<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('specialty');
            $table->string('qualifications');
            $table->integer('experience_years')->default(5);
            $table->text('bio');
            $table->string('languages')->default('English, Swahili');
            $table->string('consultation_days')->default('Mon - Fri');
            $table->string('consultation_hours')->default('09:00 AM - 04:00 PM');
            $table->string('room_number')->nullable();
            $table->decimal('rating', 3, 1)->default(4.9);
            $table->integer('review_count')->default(120);
            $table->string('image')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
