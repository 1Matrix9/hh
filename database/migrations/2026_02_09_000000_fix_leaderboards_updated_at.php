<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw statement to avoid requiring doctrine/dbal for column modification
        DB::statement("ALTER TABLE `leaderboards` MODIFY `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to NOT NULL without default (original state from initial migration)
        DB::statement("ALTER TABLE `leaderboards` MODIFY `updated_at` TIMESTAMP NOT NULL");
    }
};
