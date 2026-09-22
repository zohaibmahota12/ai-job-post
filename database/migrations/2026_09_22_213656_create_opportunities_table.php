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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('company')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('external_id')->nullable();
            $table->string('location')->nullable();
            $table->string('job_type')->nullable();
            $table->string('workplace')->nullable();
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->unsignedSmallInteger('required_experience_years')->nullable();
            $table->json('raw_data')->nullable();
            $table->json('normalized_data')->nullable();
            $table->string('status')->default('open');
            $table->char('content_hash', 64);
            $table->timestamps();

            $table->unique('content_hash');
            $table->unique(['source_id', 'external_id']);
            $table->index('status');
            $table->index('posted_at');
            $table->index('job_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
