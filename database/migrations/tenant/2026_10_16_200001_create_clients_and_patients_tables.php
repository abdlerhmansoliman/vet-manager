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
            $table->string('client_type', 32)->default('individual');
            $table->string('name', 191);
            $table->string('contact_person', 191)->nullable();
            $table->string('phone_primary', 32);
            $table->string('phone_secondary', 32)->nullable();
            $table->text('physical_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name', 'idx_clients_name');
            $table->index('phone_primary', 'idx_clients_phone_primary');
            $table->index('client_type', 'idx_clients_type');
        });

        Schema::connection('tenant')->create('patients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('name_or_tag', 100);
            $table->string('species', 64);
            $table->string('breed', 100)->nullable();
            $table->string('gender', 32)->default('unknown');
            $table->unsignedSmallInteger('age')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_approximate_age')->default(false);
            $table->string('microchip_number', 32)->nullable()->unique('uk_patients_microchip');
            $table->string('ear_tag_id', 64)->nullable()->index('idx_patients_ear_tag');
            $table->string('coat_color_markings', 191)->nullable();
            $table->decimal('current_weight_kg', 8, 3)->nullable();
            $table->timestamp('weight_recorded_at')->nullable();
            $table->text('history')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamp('deceased_at')->nullable();
            $table->json('allergies')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status'], 'idx_patients_client_status');
            $table->index(['species', 'breed'], 'idx_patients_species_breed');
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
