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
        if (Schema::connection('tenant')->hasTable('dose_administrations')) {
            Schema::connection('tenant')->table('dose_administrations', function (Blueprint $table): void {
                if (! Schema::connection('tenant')->hasColumn('dose_administrations', 'visit_id')) {
                    $table->uuid('visit_id')->nullable()->after('case_id');
                    $table->foreign('visit_id')
                        ->references('id')
                        ->on('visits')
                        ->onDelete('cascade');
                    $table->index('visit_id', 'idx_dose_visit');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('tenant')->hasTable('dose_administrations')) {
            Schema::connection('tenant')->table('dose_administrations', function (Blueprint $table): void {
                if (Schema::connection('tenant')->hasColumn('dose_administrations', 'visit_id')) {
                    $table->dropForeign(['visit_id']);
                    $table->dropIndex('idx_dose_visit');
                    $table->dropColumn('visit_id');
                }
            });
        }
    }
};
