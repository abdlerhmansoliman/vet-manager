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
        Schema::connection('tenant')->table('inventory_products', function (Blueprint $table): void {
            $table->decimal('reconciliation_deficit', 12, 4)->default('0.0000')->after('packaging_factor');
        });

        Schema::connection('tenant')->table('inventory_batches', function (Blueprint $table): void {
            $table->date('expiration_date')->nullable()->change();
        });

        if (DB::connection('tenant')->getDriverName() === 'mysql') {
            DB::connection('tenant')->unprepared(<<<'SQL'
                CREATE TRIGGER trg_ledger_prevent_update BEFORE UPDATE ON inventory_movement_ledger
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inventory movement ledger records are strictly immutable and cannot be updated.';
                END;
            SQL);

            DB::connection('tenant')->unprepared(<<<'SQL'
                CREATE TRIGGER trg_ledger_prevent_delete BEFORE DELETE ON inventory_movement_ledger
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inventory movement ledger records are strictly immutable and cannot be deleted.';
                END;
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection('tenant')->getDriverName() === 'mysql') {
            DB::connection('tenant')->unprepared('DROP TRIGGER IF EXISTS trg_ledger_prevent_delete');
            DB::connection('tenant')->unprepared('DROP TRIGGER IF EXISTS trg_ledger_prevent_update');
        }

        Schema::connection('tenant')->table('inventory_products', function (Blueprint $table): void {
            $table->dropColumn('reconciliation_deficit');
        });
    }
};
