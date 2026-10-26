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
        Schema::connection('tenant')->create('clinical_progress_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('clinical_case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignUuid('vet_id')->constrained('users')->cascadeOnDelete();

            // Vital Signs (nullable)
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->integer('heart_rate_bpm')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->integer('systolic_bp')->nullable();
            $table->integer('diastolic_bp')->nullable();
            $table->string('mucous_membrane')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();

            // SOAP Notes (nullable)
            $table->text('subjective_notes')->nullable();
            $table->text('objective_notes')->nullable();
            $table->text('assessment_notes')->nullable();
            $table->text('plan_notes')->nullable();

            // Signature & Verification
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();

            $table->timestamps();

            $table->index(['clinical_case_id', 'created_at'], 'idx_progress_notes_case_created');
            $table->index('vet_id', 'idx_progress_notes_vet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('clinical_progress_notes');
    }
};
