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
        Schema::create('triage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained('visits')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('triage_nurse_id')->constrained('users')->cascadeOnDelete();

            // Vital Signs
            $table->unsignedSmallInteger('systolic_bp')->nullable(); // mmHg
            $table->unsignedSmallInteger('diastolic_bp')->nullable(); // mmHg
            $table->unsignedSmallInteger('pulse_rate')->nullable(); // bpm
            $table->unsignedSmallInteger('respiratory_rate')->nullable(); // breaths/min
            $table->decimal('temperature', 4, 1)->nullable(); // °C
            $table->unsignedTinyInteger('oxygen_saturation')->nullable(); // % SpO2
            $table->unsignedTinyInteger('pain_score')->nullable(); // 0 to 10 scale
            $table->decimal('weight_kg', 5, 2)->nullable(); // kg
            $table->decimal('height_cm', 5, 2)->nullable(); // cm
            $table->decimal('bmi', 4, 1)->nullable(); // kg/m^2
            $table->decimal('blood_glucose', 5, 1)->nullable(); // mmol/L

            // Clinical Triage Categorization (Standard Emergency Severity / Manchester Clinical Scale)
            $table->enum('triage_category', [
                'emergency',    // Cat 1: Immediate resuscitation / life-threatening
                'very_urgent',  // Cat 2: High risk / severe pain / altered vitals
                'urgent',       // Cat 3: Acute illness / stable vitals
                'standard',     // Cat 4: Routine outpatient presentation
                'non_urgent',   // Cat 5: Chronic / minor complaints
            ])->default('standard')->index();

            $table->text('presenting_complaint');
            $table->text('nursing_notes')->nullable();

            // Destination Clinic / Physician Handoff
            $table->foreignId('referred_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('referred_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();

            $table->timestamp('triaged_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triage_records');
    }
};
