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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('mrn', 32)->unique()->index();
            $table->string('first_name', 100)->index();
            $table->string('last_name', 100)->index();
            $table->date('date_of_birth')->index();
            $table->enum('gender', ['male', 'female', 'other', 'unspecified'])->default('unspecified');
            $table->string('phone', 30)->index();
            $table->string('email', 255)->nullable()->index();
            $table->string('national_id', 50)->nullable()->index();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->default('Nairobi');
            $table->string('blood_group', 10)->nullable();
            $table->text('allergies')->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('emergency_contact_relationship', 50)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
