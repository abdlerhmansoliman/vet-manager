<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. inventory_batches: unique (product_id, batch_number)
        Schema::connection('tenant')->table('inventory_batches', function (Blueprint $table): void {
            $table->unique(['product_id', 'batch_number'], 'unique_product_batch');
        });

        // 2. inventory_containers: unique (batch_id, container_identifier)
        Schema::connection('tenant')->table('inventory_containers', function (Blueprint $table): void {
            $table->unique(['batch_id', 'container_identifier'], 'unique_batch_container');
        });

        // 3. inventory_movement_ledger: add operation_id, product_balance_after, composite index, restrict foreign keys
        Schema::connection('tenant')->table('inventory_movement_ledger', function (Blueprint $table): void {
            $table->uuid('operation_id')->nullable()->after('idempotency_key')->index();
            $table->decimal('product_balance_after', 12, 4)->nullable()->after('balance_after');
            $table->index(['product_id', 'created_at'], 'idx_ledger_product_created');

            // Drop cascading foreign keys and replace with RESTRICT on delete to protect audit trails
            $table->dropForeign(['recorded_by_user_id']);
            $table->dropForeign(['product_id']);

            $table->foreign('recorded_by_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('inventory_products')
                ->restrictOnDelete();
        });

        // 4. Check constraints on MySQL / MariaDB engines
        if (DB::connection('tenant')->getDriverName() === 'mysql') {
            DB::connection('tenant')->statement(
                'ALTER TABLE inventory_containers ADD CONSTRAINT chk_container_balance_non_negative CHECK (current_base_balance >= 0)'
            );
            DB::connection('tenant')->statement(
                'ALTER TABLE inventory_containers ADD CONSTRAINT chk_container_balance_max CHECK (current_base_balance <= initial_base_quantity)'
            );
            DB::connection('tenant')->statement(
                'ALTER TABLE inventory_containers ADD CONSTRAINT chk_container_initial_positive CHECK (initial_base_quantity > 0)'
            );
            DB::connection('tenant')->statement(
                'ALTER TABLE inventory_products ADD CONSTRAINT chk_product_packaging_factor CHECK (packaging_factor > 0)'
            );
            DB::connection('tenant')->statement(
                'ALTER TABLE inventory_products ADD CONSTRAINT chk_product_reorder_threshold CHECK (reorder_threshold >= 0)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection('tenant')->getDriverName() === 'mysql') {
            DB::connection('tenant')->statement('ALTER TABLE inventory_products DROP CONSTRAINT chk_product_reorder_threshold');
            DB::connection('tenant')->statement('ALTER TABLE inventory_products DROP CONSTRAINT chk_product_packaging_factor');
            DB::connection('tenant')->statement('ALTER TABLE inventory_containers DROP CONSTRAINT chk_container_initial_positive');
            DB::connection('tenant')->statement('ALTER TABLE inventory_containers DROP CONSTRAINT chk_container_balance_max');
            DB::connection('tenant')->statement('ALTER TABLE inventory_containers DROP CONSTRAINT chk_container_balance_non_negative');
        }

        Schema::connection('tenant')->table('inventory_movement_ledger', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['recorded_by_user_id']);

            $table->foreign('recorded_by_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inventory_products')->cascadeOnDelete();

            $table->dropIndex('idx_ledger_product_created');
            $table->dropColumn(['product_balance_after', 'operation_id']);
        });

        Schema::connection('tenant')->table('inventory_containers', function (Blueprint $table): void {
            $table->dropUnique('unique_batch_container');
        });

        Schema::connection('tenant')->table('inventory_batches', function (Blueprint $table): void {
            $table->dropUnique('unique_product_batch');
        });
    }
};
