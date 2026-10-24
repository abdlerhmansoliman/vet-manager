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
        Schema::connection('tenant')->create('cases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('case_number', 32)->unique('uk_cases_case_number');
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUuid('primary_veterinarian_id')->constrained('users')->restrictOnDelete();
            $table->string('case_type', 32)->default('outpatient');
            $table->string('status', 32)->default('open');
            $table->string('priority', 32)->default('routine');
            $table->string('chief_complaint', 255);
            $table->text('provisional_diagnosis')->nullable();
            $table->text('final_diagnosis')->nullable();
            $table->timestamp('admitted_at')->useCurrent();
            $table->timestamp('discharged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status'], 'idx_cases_patient_status');
            $table->index(['primary_veterinarian_id', 'status'], 'idx_cases_vet_status');
            $table->index(['case_type', 'admitted_at'], 'idx_cases_type_admitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('cases');
    }
};
