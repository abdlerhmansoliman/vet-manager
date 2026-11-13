<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection('tenant')->hasTable('species')) {
            Schema::connection('tenant')->create('species', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name', 64);
                $table->string('code', 64)->unique('uk_species_code');
                $table->string('icon', 32)->nullable();
                $table->boolean('is_system_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'name'], 'idx_species_active_name');
                $table->index('updated_at', 'idx_species_updated_at');
            });

            $now = now();
            $defaults = [
                ['name' => 'Canine', 'code' => 'canine', 'icon' => 'dog'],
                ['name' => 'Feline', 'code' => 'feline', 'icon' => 'cat'],
                ['name' => 'Equine', 'code' => 'equine', 'icon' => 'horse'],
                ['name' => 'Bovine', 'code' => 'bovine', 'icon' => 'cow'],
                ['name' => 'Ovine', 'code' => 'ovine', 'icon' => 'sheep'],
                ['name' => 'Caprine', 'code' => 'caprine', 'icon' => 'goat'],
                ['name' => 'Avian', 'code' => 'avian', 'icon' => 'bird'],
                ['name' => 'Camel', 'code' => 'camel', 'icon' => 'camel'],
                ['name' => 'Other', 'code' => 'other', 'icon' => 'other'],
            ];

            $rows = array_map(function (array $item) use ($now): array {
                return [
                    'id' => (string) Str::uuid(),
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'icon' => $item['icon'],
                    'is_system_default' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $defaults);

            DB::connection('tenant')->table('species')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('species');
    }
};
