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
        Schema::connection('tenant')->table('inventory_movement_ledger', function (Blueprint $table): void {
            $table->decimal('balance_after', 12, 4)->nullable()->change();

            $table->dropForeign(['batch_id']);
            $table->dropForeign(['container_id']);
            $table->dropForeign(['witnessed_by_user_id']);

            $table->foreign('batch_id')
                ->references('id')
                ->on('inventory_batches')
                ->restrictOnDelete();

            $table->foreign('container_id')
                ->references('id')
                ->on('inventory_containers')
                ->restrictOnDelete();

            $table->foreign('witnessed_by_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('inventory_movement_ledger', function (Blueprint $table): void {
            $table->dropForeign(['witnessed_by_user_id']);
            $table->dropForeign(['container_id']);
            $table->dropForeign(['batch_id']);

            $table->foreign('batch_id')
                ->nullable()
                ->references('id')
                ->on('inventory_batches')
                ->nullOnDelete();

            $table->foreign('container_id')
                ->nullable()
                ->references('id')
                ->on('inventory_containers')
                ->nullOnDelete();

            $table->foreign('witnessed_by_user_id')
                ->nullable()
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->decimal('balance_after', 12, 4)->nullable(false)->change();
        });
    }
};
