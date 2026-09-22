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
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('content');
            $table->boolean('generated_by_ai')->default(false)->after('status');
        });

        Schema::create('proposal_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->string('subject')->nullable();
            $table->string('source');
            $table->timestamps();

            $table->index(['proposal_id', 'created_at']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('notes');
            $table->timestamp('follow_up_at')->nullable()->after('contact_name');
            $table->string('external_url')->nullable()->after('follow_up_at');
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'follow_up_at', 'external_url']);
        });

        Schema::dropIfExists('proposal_versions');

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['subject', 'generated_by_ai']);
        });
    }
};
