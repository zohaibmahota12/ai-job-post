<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->string('key')->nullable()->after('id');
            $table->string('type')->nullable()->after('driver');
            $table->timestamp('last_run_at')->nullable()->after('config');
            $table->timestamp('last_success_at')->nullable()->after('last_run_at');
            $table->text('last_error')->nullable()->after('last_success_at');
        });

        foreach (DB::table('sources')->orderBy('id')->get() as $source) {
            DB::table('sources')->where('id', $source->id)->update([
                'key' => $source->driver,
                'type' => $source->driver,
            ]);
        }

        Schema::table('sources', function (Blueprint $table) {
            $table->unique('key');
            $table->dropUnique(['driver']);
            $table->index('driver');
            $table->index('type');
        });

        Schema::table('source_runs', function (Blueprint $table) {
            $table->unsignedInteger('items_updated')->default(0)->after('items_created');
            $table->unsignedInteger('items_duplicated')->default(0)->after('items_skipped');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('canonical_url', 2048)->nullable()->after('source_url');
            $table->index('canonical_url');
            $table->index('workplace');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['canonical_url']);
            $table->dropIndex(['workplace']);
            $table->dropIndex(['location']);
            $table->dropColumn('canonical_url');
        });

        Schema::table('source_runs', function (Blueprint $table) {
            $table->dropColumn(['items_updated', 'items_duplicated']);
        });

        Schema::table('sources', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropIndex(['driver']);
            $table->dropIndex(['type']);
            $table->unique('driver');
            $table->dropColumn(['key', 'type', 'last_run_at', 'last_success_at', 'last_error']);
        });
    }
};
