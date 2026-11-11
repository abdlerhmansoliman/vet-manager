<?php

declare(strict_types=1);

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
        Schema::connection('tenant')->create('dose_administrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUuid('product_id')->constrained('inventory_products')->restrictOnDelete();
            $table->foreignUuid('administered_by_user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('dose_amount_base_units', 12, 4);
            $table->string('route_administered', 32)->nullable();
            $table->text('clinical_notes')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'created_at'], 'idx_dose_case_created');
            $table->index(['patient_id', 'created_at'], 'idx_dose_patient_created');
            $table->index(['product_id'], 'idx_dose_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('dose_administrations');
    }
};
