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
        Schema::create('dispensings', function (Blueprint $table) {
            $table->id();
            $table->string('dispensation_number', 50)->unique();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->foreignId('prescription_item_id')->constrained('prescription_items')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
            $table->foreignId('medicine_batch_id')->constrained('medicine_batches')->cascadeOnDelete();
            $table->integer('quantity_dispensed');
            $table->foreignId('dispensed_by')->constrained('users')->cascadeOnDelete();
            $table->text('pharmacist_notes')->nullable();
            $table->timestamp('dispensed_at')->useCurrent();
            $table->timestamps();

            $table->index(['prescription_id', 'dispensed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispensings');
    }
};
