<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();
            $table->string('email', 255)->index();
            $table->string('password')->nullable();
            // Only a SHA-256 hash of the emailed token is stored, never the token itself.
            $table->string('token_hash', 64)->nullable()->index();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('must_change_password')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_accounts');
    }
};
