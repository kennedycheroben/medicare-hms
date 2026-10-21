<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6 refinements: allow partially-completed consultation drafts, add
     * idempotency keys for prescriptions/dispensing, record non-dispensing reasons,
     * and enforce non-negative stock at the database level.
     */
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->text('chief_complaint')->nullable()->change();
            $table->text('history_present_illness')->nullable()->change();
            $table->text('examination_findings')->nullable()->change();
            $table->text('assessment')->nullable()->change();
            $table->string('primary_diagnosis')->nullable()->change();
            $table->text('treatment_plan')->nullable()->change();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('submission_token', 64)->nullable()->unique()->after('prescription_number');
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            $table->string('non_dispense_reason', 500)->nullable()->after('dispense_status');
        });

        Schema::table('dispensings', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('dispensation_number');
            $table->unique(['idempotency_key', 'medicine_batch_id'], 'dispensings_idem_batch_unique');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('reason', 255)->nullable()->after('movement_type');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE medicine_batches ADD CONSTRAINT medicine_batches_qty_nonneg CHECK (quantity_current >= 0)');
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE medicine_batches DROP CONSTRAINT medicine_batches_qty_nonneg');
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('reason');
        });

        Schema::table('dispensings', function (Blueprint $table) {
            $table->dropUnique('dispensings_idem_batch_unique');
            $table->dropColumn('idempotency_key');
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropColumn('non_dispense_reason');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropUnique(['submission_token']);
            $table->dropColumn('submission_token');
        });
        // Consultation column nullability is intentionally not reverted: doing so could fail on existing drafts.
    }
};
