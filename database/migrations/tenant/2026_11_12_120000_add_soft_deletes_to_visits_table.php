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
        if (Schema::connection('tenant')->hasTable('visits')) {
            Schema::connection('tenant')->table('visits', function (Blueprint $table): void {
                if (! Schema::connection('tenant')->hasColumn('visits', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('tenant')->hasTable('visits')) {
            Schema::connection('tenant')->table('visits', function (Blueprint $table): void {
                if (Schema::connection('tenant')->hasColumn('visits', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
