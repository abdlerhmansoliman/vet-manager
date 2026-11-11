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
        Schema::connection('tenant')->create('visits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->string('title', 191);
            $table->string('visit_type', 64)->default('outpatient');
            $table->string('status', 32)->default('open');
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by', 191)->nullable();
            $table->text('reason_for_visit')->nullable();
            $table->timestamp('visit_date');
            $table->timestamps();

            $table->foreign('case_id')
                ->references('id')
                ->on('cases')
                ->onDelete('cascade');

            $table->index(['case_id', 'status']);
            $table->index('visit_date');
        });

        if (Schema::connection('tenant')->hasTable('clinical_progress_notes')) {
            Schema::connection('tenant')->table('clinical_progress_notes', function (Blueprint $table): void {
                if (! Schema::connection('tenant')->hasColumn('clinical_progress_notes', 'visit_id')) {
                    $table->uuid('visit_id')->nullable()->after('clinical_case_id');
                    $table->foreign('visit_id')
                        ->references('id')
                        ->on('visits')
                        ->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('tenant')->hasTable('clinical_progress_notes')) {
            Schema::connection('tenant')->table('clinical_progress_notes', function (Blueprint $table): void {
                if (Schema::connection('tenant')->hasColumn('clinical_progress_notes', 'visit_id')) {
                    $table->dropForeign(['visit_id']);
                    $table->dropColumn('visit_id');
                }
            });
        }

        Schema::connection('tenant')->dropIfExists('visits');
    }
};
