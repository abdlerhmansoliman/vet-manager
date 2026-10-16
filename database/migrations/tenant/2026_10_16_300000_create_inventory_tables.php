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
        Schema::connection('tenant')->create('inventory_products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('base_unit')->default('ml');
            $table->boolean('is_controlled')->default(false);
            $table->string('controlled_schedule')->default('uncontrolled');
            $table->decimal('reorder_threshold', 12, 4)->default('0.0000');
            $table->string('package_description')->nullable();
            $table->decimal('packaging_factor', 12, 4)->default('1.0000');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('inventory_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('inventory_products')->cascadeOnDelete();
            $table->string('batch_number');
            $table->date('expiration_date')->index();
            $table->decimal('cost_per_base_unit', 12, 4)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'expiration_date']);
        });

        Schema::connection('tenant')->create('inventory_containers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('inventory_products')->cascadeOnDelete();
            $table->foreignUuid('batch_id')->constrained('inventory_batches')->cascadeOnDelete();
            $table->string('container_identifier');
            $table->decimal('initial_base_quantity', 12, 4);
            $table->decimal('current_base_balance', 12, 4);
            $table->string('status')->default('sealed')->index();
            $table->timestamp('opened_at')->nullable();
            $table->foreignUuid('opened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('depleted_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['batch_id', 'status']);
        });

        Schema::connection('tenant')->create('inventory_movement_ledger', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('idempotency_key')->nullable()->index();
            $table->foreignUuid('product_id')->constrained('inventory_products')->cascadeOnDelete();
            $table->foreignUuid('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignUuid('container_id')->nullable()->constrained('inventory_containers')->nullOnDelete();
            $table->string('movement_type');
            $table->decimal('quantity', 12, 4);
            $table->decimal('balance_after', 12, 4);
            $table->string('reason')->nullable();
            $table->foreignUuid('witnessed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('inventory_movement_ledger');
        Schema::connection('tenant')->dropIfExists('inventory_containers');
        Schema::connection('tenant')->dropIfExists('inventory_batches');
        Schema::connection('tenant')->dropIfExists('inventory_products');
    }
};
