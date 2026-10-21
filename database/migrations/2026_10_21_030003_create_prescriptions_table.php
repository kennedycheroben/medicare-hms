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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number', 50)->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('prescriber_id')->constrained('users')->cascadeOnDelete();
            $table->text('clinical_notes')->nullable();
            $table->string('status', 30)->default('draft'); // draft, finalized, partially_dispensed, dispensed, cancelled
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('status');
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->nullOnDelete();
            $table->string('medication_name');
            $table->string('dosage', 100); // e.g. 500mg, 1 tablet
            $table->string('route', 50)->default('Oral'); // Oral, IV, IM, Topical, Inhalation, etc.
            $table->string('frequency', 100); // e.g. TDS (3 times daily), BD, OD, PRN
            $table->string('duration', 100); // e.g. 5 days, 7 days
            $table->integer('quantity_prescribed');
            $table->integer('quantity_dispensed')->default(0);
            $table->string('instructions', 500)->nullable();
            $table->string('dispense_status', 30)->default('pending'); // pending, partially_dispensed, dispensed, cancelled
            $table->timestamps();

            $table->index(['prescription_id', 'dispense_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
