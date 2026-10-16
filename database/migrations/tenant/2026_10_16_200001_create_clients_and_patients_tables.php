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
        Schema::connection('tenant')->create('clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type')->default('individual_pet_owner'); // individual_pet_owner, farm_enterprise
            $table->string('first_name');
            $table->string('last_name');
            $table->string('business_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('emergency_contact')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('patients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name');
            $table->string('species'); // canine, feline, equine, bovine, etc.
            $table->string('breed')->nullable();
            $table->string('gender')->default('unknown'); // male_intact, male_neutered, female_intact, female_spayed
            $table->date('date_of_birth')->nullable();
            $table->decimal('current_weight_kg', 8, 3)->nullable();
            $table->string('microchip_number')->nullable()->index();
            $table->json('allergies')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('patients');
        Schema::connection('tenant')->dropIfExists('clients');
    }
};
