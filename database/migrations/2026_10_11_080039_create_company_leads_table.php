<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_leads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('website_url')->nullable();
            $table->string('website_status', 30)
                ->default('unknown')
                ->index();

            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('lead_status', 30)
                ->default('new')
                ->index();

            $table->unsignedTinyInteger('lead_score')
                ->default(0)
                ->index();

            $table->text('notes')->nullable();

            $table->timestamp('website_checked_at')->nullable();
            $table->timestamp('contacted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_leads');
    }
};
