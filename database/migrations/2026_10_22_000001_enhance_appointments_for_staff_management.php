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
            $table->string('status', 30)->default('pending')->change();
            $table->string('cancellation_reason', 500)->nullable()->after('status');
            $table->string('reschedule_reason', 500)->nullable()->after('cancellation_reason');
            $table->timestamp('confirmed_at')->nullable()->after('reschedule_reason');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('confirmed_by');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rescheduled_at')->nullable()->after('cancelled_by');
            $table->foreignId('rescheduled_by')->nullable()->after('rescheduled_at')->constrained('users')->nullOnDelete();

            $table->index(['status', 'appointment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['status', 'appointment_date']);
            $table->dropForeign(['confirmed_by']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['rescheduled_by']);
            $table->dropColumn([
                'cancellation_reason',
                'reschedule_reason',
                'confirmed_at',
                'confirmed_by',
                'cancelled_at',
                'cancelled_by',
                'rescheduled_at',
                'rescheduled_by',
            ]);
        });
    }
};
