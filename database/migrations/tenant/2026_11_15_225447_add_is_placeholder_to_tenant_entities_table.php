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
        if (Schema::connection('tenant')->hasTable('clients')) {
            Schema::connection('tenant')->table('clients', function (Blueprint $table): void {
                if (! Schema::connection('tenant')->hasColumn('clients', 'is_placeholder')) {
                    $table->boolean('is_placeholder')->default(false)->after('notes');
                }
            });
        }

        if (Schema::connection('tenant')->hasTable('patients')) {
            Schema::connection('tenant')->table('patients', function (Blueprint $table): void {
                if (! Schema::connection('tenant')->hasColumn('patients', 'is_placeholder')) {
                    $table->boolean('is_placeholder')->default(false)->after('notes');
                }
            });
        }

        if (Schema::connection('tenant')->hasTable('cases')) {
            Schema::connection('tenant')->table('cases', function (Blueprint $table): void {
                if (! Schema::connection('tenant')->hasColumn('cases', 'is_placeholder')) {
                    $table->boolean('is_placeholder')->default(false)->after('chief_complaint');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('tenant')->hasTable('clients')) {
            Schema::connection('tenant')->table('clients', function (Blueprint $table): void {
                if (Schema::connection('tenant')->hasColumn('clients', 'is_placeholder')) {
                    $table->dropColumn('is_placeholder');
                }
            });
        }

        if (Schema::connection('tenant')->hasTable('patients')) {
            Schema::connection('tenant')->table('patients', function (Blueprint $table): void {
                if (Schema::connection('tenant')->hasColumn('patients', 'is_placeholder')) {
                    $table->dropColumn('is_placeholder');
                }
            });
        }

        if (Schema::connection('tenant')->hasTable('cases')) {
            Schema::connection('tenant')->table('cases', function (Blueprint $table): void {
                if (Schema::connection('tenant')->hasColumn('cases', 'is_placeholder')) {
                    $table->dropColumn('is_placeholder');
                }
            });
        }
    }
};
