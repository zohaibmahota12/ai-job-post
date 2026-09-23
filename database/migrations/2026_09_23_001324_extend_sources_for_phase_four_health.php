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
        Schema::table('sources', function (Blueprint $table) {
            $table->unsignedInteger('consecutive_failures')->default(0)->after('last_error');
            $table->timestamp('last_failure_at')->nullable()->after('consecutive_failures');
            $table->unsignedInteger('last_item_count')->nullable()->after('last_failure_at');
            $table->unsignedInteger('last_created_count')->nullable()->after('last_item_count');
            $table->unsignedInteger('last_updated_count')->nullable()->after('last_created_count');
            $table->unsignedInteger('last_duplicated_count')->nullable()->after('last_updated_count');
            $table->unsignedInteger('last_duration_ms')->nullable()->after('last_duplicated_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn([
                'consecutive_failures',
                'last_failure_at',
                'last_item_count',
                'last_created_count',
                'last_updated_count',
                'last_duplicated_count',
                'last_duration_ms',
            ]);
        });
    }
};
