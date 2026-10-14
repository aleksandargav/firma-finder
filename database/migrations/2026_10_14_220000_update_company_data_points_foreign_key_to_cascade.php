<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_data_points_cascade_backup', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('external_identifier')->nullable();
            $table->string('identifier_type')->nullable();
            $table->string('field');
            $table->text('value');
            $table->string('source');
            $table->string('source_url')->nullable();
            $table->string('confidence');
            $table->unsignedTinyInteger('identity_match_score')->default(0);
            $table->string('data_license_status');
            $table->string('status');
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO company_data_points_cascade_backup (
                id, company_id, external_identifier, identifier_type, field, value,
                source, source_url, confidence, identity_match_score,
                data_license_status, status, retrieved_at, verified_at,
                verified_by, notes, created_at, updated_at
            )
            SELECT
                id, company_id, external_identifier, identifier_type, field, value,
                source, source_url, confidence, identity_match_score,
                data_license_status, status, retrieved_at, verified_at,
                verified_by, notes, created_at, updated_at
            FROM company_data_points
        ');

        Schema::dropIfExists('company_data_points');

        Schema::create('company_data_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->string('external_identifier')->nullable();
            $table->string('identifier_type')->nullable();

            $table->string('field');
            $table->text('value');

            $table->string('source');
            $table->string('source_url')->nullable();

            $table->string('confidence');
            $table->unsignedTinyInteger('identity_match_score')->default(0);

            $table->string('data_license_status');
            $table->string('status');

            $table->timestamp('retrieved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'field']);
            $table->index(['company_id', 'field', 'status']);
            $table->index(['source', 'status']);
            $table->index(['external_identifier', 'identifier_type']);
        });

        DB::statement('
            INSERT INTO company_data_points (
                id, company_id, external_identifier, identifier_type, field, value,
                source, source_url, confidence, identity_match_score,
                data_license_status, status, retrieved_at, verified_at,
                verified_by, notes, created_at, updated_at
            )
            SELECT
                id, company_id, external_identifier, identifier_type, field, value,
                source, source_url, confidence, identity_match_score,
                data_license_status, status, retrieved_at, verified_at,
                verified_by, notes, created_at, updated_at
            FROM company_data_points_cascade_backup
        ');

        Schema::dropIfExists('company_data_points_cascade_backup');
    }

    public function down(): void
    {
        Schema::create('company_data_points_cascade_backup', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('external_identifier')->nullable();
            $table->string('identifier_type')->nullable();
            $table->string('field');
            $table->text('value');
            $table->string('source');
            $table->string('source_url')->nullable();
            $table->string('confidence');
            $table->unsignedTinyInteger('identity_match_score')->default(0);
            $table->string('data_license_status');
            $table->string('status');
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO company_data_points_cascade_backup (
                id, company_id, external_identifier, identifier_type, field, value,
                source, source_url, confidence, identity_match_score,
                data_license_status, status, retrieved_at, verified_at,
                verified_by, notes, created_at, updated_at
            )
            SELECT
                id, company_id, external_identifier, identifier_type, field, value,
                source, source_url, confidence, identity_match_score,
                data_license_status, status, retrieved_at, verified_at,
                verified_by, notes, created_at, updated_at
            FROM company_data_points
        ');

        Schema::dropIfExists('company_data_points');

        Schema::create('company_data_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->string('external_identifier')->nullable();
            $table->string('identifier_type')->nullable();

            $table->string('field');
            $table->text('value');

            $table->string('source');
            $table->string('source_url')->nullable();

            $table->string('confidence');
            $table->unsignedTinyInteger('identity_match_score')->default(0);

            $table->string('data_license_status');
            $table->string('status');

            $table->timestamp('retrieved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'field', 'status']);
            $table->index(['source', 'status']);
            $table->index(['external_identifier', 'identifier_type']);
        });

        DB::statement('
            INSERT INTO company_data_points (
                id, company_id, external_identifier, identifier_type, field, value,
                source, source_url, confidence, identity_match_score,
                data_license_status, status, retrieved_at, verified_at,
                verified_by, notes, created_at, updated_at
            )
            SELECT
                id, company_id, external_identifier, identifier_type, field, value,
                source, source_url, confidence, identity_match_score,
                data_license_status, status, retrieved_at, verified_at,
                verified_by, notes, created_at, updated_at
            FROM company_data_points_cascade_backup
        ');

        Schema::dropIfExists('company_data_points_cascade_backup');
    }
};
