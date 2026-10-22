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
        Schema::table('doctors', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('is_available');
            $table->timestamp('published_at')->nullable()->after('is_published');
            $table->foreignId('approved_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->string('publication_notes', 500)->nullable()->after('approved_by');
        });

        Schema::create('doctor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5 stars
            $table->text('comment');
            $table->boolean('public_consent')->default(false); // Explicit consent for public website publication
            $table->string('display_name_type', 30)->default('anonymous'); // 'full_name', 'initials', 'anonymous'
            $table->string('status', 30)->default('pending_moderation'); // 'pending_moderation', 'approved', 'rejected'
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderation_notes', 500)->nullable();
            $table->timestamps();

            // Enforce strictly one review per verified clinical visit encounter
            $table->unique('visit_id');
            $table->index(['doctor_id', 'status']);
            $table->index(['patient_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_reviews');

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['is_published', 'published_at', 'approved_by', 'publication_notes']);
        });
    }
};
