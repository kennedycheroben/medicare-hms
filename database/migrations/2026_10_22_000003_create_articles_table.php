<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->string('summary', 400);
            $table->text('body');
            $table->string('category', 60)->default('Announcement');
            $table->string('cover_image_path')->nullable();
            $table->string('author_name', 120);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft | published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
