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
        Schema::connection('landlord')->create('tenants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('database_name')->unique();
            $table->unsignedBigInteger('storage_limit_bytes')->default(21474836480); // 20 GB default
            $table->unsignedBigInteger('storage_used_bytes')->default(0);
            $table->string('status')->default('active')->index();
            $table->string('plan')->default('clinic');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('tenants');
    }
};
