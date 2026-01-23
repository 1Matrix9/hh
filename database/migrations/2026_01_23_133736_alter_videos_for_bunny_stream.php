<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Bunny fields
            if (!Schema::hasColumn('videos', 'bunny_guid')) {
                $table->uuid('bunny_guid')->nullable()->unique()->after('title');
            }
            if (!Schema::hasColumn('videos', 'status')) {
                $table->string('status')->default('pending')->after('bunny_guid');
            }
            if (!Schema::hasColumn('videos', 'meta')) {
                $table->json('meta')->nullable()->after('status');
            }

            // Duration -> unsignedInteger nullable (requires doctrine/dbal for change)
            if (Schema::hasColumn('videos', 'duration')) {
                $table->unsignedInteger('duration')->nullable()->change();
            }

            // Make video_url nullable
            if (Schema::hasColumn('videos', 'video_url')) {
                $table->string('video_url')->nullable()->change();
            }

            // Ensure order_index default and index
            if (Schema::hasColumn('videos', 'order_index')) {
                $table->unsignedInteger('order_index')->default(0)->change();
                $table->index('order_index', 'videos_order_index_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Careful reversing in production; keep it simple
            if (Schema::hasColumn('videos', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('videos', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('videos', 'bunny_guid')) {
                $table->dropUnique('videos_bunny_guid_unique');
                $table->dropColumn('bunny_guid');
            }
            if (Schema::hasColumn('videos', 'order_index')) {
                // Optional: drop index if it exists
                $table->dropIndex('videos_order_index_idx');
            }
        });
    }
};
