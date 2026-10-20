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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('receptionist')->after('email')->index();
            $table->boolean('is_active')->default(true)->after('role')->index();
            $table->foreignId('department_id')->nullable()->after('is_active')->constrained('departments')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->after('department_id')->constrained('doctors')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['doctor_id']);
            $table->dropColumn([
                'role',
                'is_active',
                'department_id',
                'doctor_id',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
