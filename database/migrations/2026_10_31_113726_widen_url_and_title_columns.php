<?php

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
        Schema::table('company_leads', function (Blueprint $table) {
            $table->string('website_url', 500)->nullable()->change();
        });

        Schema::table('company_website_audits', function (Blueprint $table) {
            $table->string('requested_url', 500)->nullable()->change();
            $table->text('final_url')->nullable()->change();
            $table->string('page_title', 500)->nullable()->change();
        });

        Schema::table('company_data_points', function (Blueprint $table) {
            $table->string('source_url', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_leads', function (Blueprint $table) {
            $table->string('website_url')->nullable()->change();
        });

        Schema::table('company_website_audits', function (Blueprint $table) {
            $table->string('requested_url')->nullable()->change();
            $table->string('final_url')->nullable()->change();
            $table->string('page_title')->nullable()->change();
        });

        Schema::table('company_data_points', function (Blueprint $table) {
            $table->string('source_url')->nullable()->change();
        });
    }
};
