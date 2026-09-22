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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->text('experience')->nullable();
            $table->unsignedTinyInteger('years_of_experience')->nullable();
            $table->string('location')->nullable();
            $table->string('preferred_job_type')->default('any');
            $table->string('remote_preference')->default('any');
            $table->decimal('minimum_budget', 12, 2)->nullable();
            $table->char('preferred_currency', 3)->default('USD');
            $table->json('keywords')->nullable();
            $table->json('excluded_keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
