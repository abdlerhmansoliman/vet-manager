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
        Schema::connection('tenant')->create('sync_mutation_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('idempotency_key', 191)->unique('uk_sync_receipts_key');
            $table->string('mutation_id', 191)->nullable();
            $table->string('entity_type', 64)->nullable();
            $table->string('entity_id', 191)->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id'], 'idx_sync_receipts_entity');
            $table->index('batch_id', 'idx_sync_receipts_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('sync_mutation_receipts');
    }
};
