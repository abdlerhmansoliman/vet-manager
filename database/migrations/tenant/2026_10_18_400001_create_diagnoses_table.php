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
        Schema::connection('tenant')->create('diagnoses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->string('diagnosis');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('diagnosed_at')->useCurrent();
            $table->timestamps();

            $table->index(['patient_id', 'status'], 'idx_diagnoses_patient_status');
            $table->index('user_id', 'idx_diagnoses_user');
            $table->index('case_id', 'idx_diagnoses_case');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('diagnoses');
    }
};
