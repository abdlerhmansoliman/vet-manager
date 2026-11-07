<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables requiring updated_at indexes for high-performance delta pulls.
     *
     * @var array<int, string>
     */
    private const TABLES = [
        'clients',
        'patients',
        'cases',
        'clinical_progress_notes',
        'inventory_products',
        'inventory_batches',
        'inventory_containers',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::connection('tenant')->table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->index('updated_at', "idx_{$tableName}_updated_at");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::connection('tenant')->table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex("idx_{$tableName}_updated_at");
            });
        }
    }
};
