<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scraped_releases', function (Blueprint $table) {
            // 1. Add MDA Name for human-readable validation
            if (!Schema::hasColumn('scraped_releases', 'mda_name')) {
                $table->string('mda_name')->nullable()->after('mda_code');
            }

            // 2. Change 'status' logic to support portal colors
            // If you already have a 'status' column, we modify it. 
            // If not, we add it.
            if (Schema::hasColumn('scraped_releases', 'status')) {
                $table->string('status')->default('circulating')->change();
            } else {
                $table->string('status')->default('circulating')->after('amount');
            }

            // 3. Add notification tracker
            $table->boolean('was_notified')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('scraped_releases', function (Blueprint $table) {
            $table->dropColumn(['mda_name', 'was_notified']);
            // Note: We don't drop 'status' if it was there before, 
            // but you can revert the default if needed.
        });
    }
};