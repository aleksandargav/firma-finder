<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('company_data_points');
    }
};
