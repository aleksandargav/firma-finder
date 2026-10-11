<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('registration_number', 8)->unique();
            $table->text('business_name');

            $table->string('municipality_code', 10)->nullable()->index();
            $table->string('municipality_name')->nullable();

            $table->string('status')->nullable()->index();
            $table->date('founded_at')->nullable();
            $table->string('legal_form')->nullable();

            $table->string('activity_code', 10)->nullable()->index();

            $table->date('source_snapshot_date');
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
