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
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('preferred_doctor_id')->nullable()->after('doctor_id')->constrained('doctors')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->after('preferred_doctor_id')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['preferred_doctor_id']);
            $table->dropForeign(['assigned_by']);
            $table->dropColumn(['preferred_doctor_id', 'assigned_by', 'assigned_at']);
        });
    }
};
