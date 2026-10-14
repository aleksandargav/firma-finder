<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_website_audits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_lead_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('requested_url')->nullable();
            $table->string('final_url')->nullable();

            $table->string('status', 30)
                ->default('pending')
                ->index();

            $table->string('audit_version', 20)
                ->default('v1');

            $table->unsignedInteger('http_status')->nullable();
            $table->boolean('is_reachable')->nullable();
            $table->boolean('has_https')->nullable();
            $table->unsignedInteger('redirect_count')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();

            $table->string('page_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['company_lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_website_audits');
    }
};
