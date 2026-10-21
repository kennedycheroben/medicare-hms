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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained('visits')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('clinician_id')->constrained('users')->cascadeOnDelete();
            $table->text('chief_complaint');
            $table->text('history_present_illness');
            $table->text('medical_history')->nullable();
            $table->text('examination_findings');
            $table->text('assessment');
            $table->string('diagnosis_type', 30)->default('confirmed'); // provisional, confirmed
            $table->string('primary_diagnosis');
            $table->text('secondary_diagnosis')->nullable();
            $table->string('icd11_code', 50)->nullable(); // Extensible clinical terminology reference point
            $table->text('treatment_plan');
            $table->text('follow_up_instructions')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status', 30)->default('draft'); // draft, signed
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
            $table->index('status');
        });

        Schema::create('consultation_addenda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('addendum_notes');
            $table->string('reason_for_amendment', 500);
            $table->timestamps();

            $table->index(['consultation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_addenda');
        Schema::dropIfExists('consultations');
    }
};
