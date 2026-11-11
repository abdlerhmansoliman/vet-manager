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
        Schema::connection('tenant')->table('inventory_batches', function (Blueprint $table): void {
            $table->decimal('initial_base_quantity', 12, 4)->default('0.0000')->after('cost_per_base_unit');
            $table->decimal('current_base_balance', 12, 4)->default('0.0000')->after('initial_base_quantity');

            $table->index(['product_id', 'current_base_balance'], 'idx_batches_product_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('inventory_batches', function (Blueprint $table): void {
            $table->dropIndex('idx_batches_product_balance');
            $table->dropColumn(['initial_base_quantity', 'current_base_balance']);
        });
    }
};
