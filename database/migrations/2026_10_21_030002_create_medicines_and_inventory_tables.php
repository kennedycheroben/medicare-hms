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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('brand_name');
            $table->string('generic_name');
            $table->string('category', 100);
            $table->string('dosage_form', 50); // Tablet, Capsule, Syrup, Injection, Ointment, Inhaler, Drops
            $table->string('strength', 50); // e.g. 500mg, 250mg/5ml
            $table->string('unit_of_measure', 50)->default('tablets'); // tablets, capsules, vials, bottles, ampoules
            $table->integer('reorder_level')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index('generic_name');
        });

        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
            $table->string('batch_number', 100);
            $table->date('expiry_date');
            $table->integer('quantity_initial');
            $table->integer('quantity_current');
            $table->decimal('unit_purchase_cost', 10, 2)->nullable();
            $table->decimal('unit_dispensing_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['medicine_id', 'batch_number']);
            $table->index(['medicine_id', 'expiry_date', 'quantity_current']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
            $table->foreignId('medicine_batch_id')->nullable()->constrained('medicine_batches')->nullOnDelete();
            $table->string('movement_type', 50); // initial_stock, dispensed, adjustment_add, adjustment_deduct, expired, return
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->string('reference_type', 100)->nullable(); // dispensation, manual_adjustment
            $table->string('reference_id', 100)->nullable();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['medicine_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('medicine_batches');
        Schema::dropIfExists('medicines');
    }
};
