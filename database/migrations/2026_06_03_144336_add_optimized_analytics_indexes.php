<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Optimize the releases lookups for transaction exclusions and quarterly filtering
        Schema::table('releases', function (Blueprint $table) {
            $table->index(['is_cancelled', 'quarter', 'subhead_id'], 'idx_releases_analytics_lookup');
        });

        // 2. Optimize subhead structure queries for quick categorization and year filters
        Schema::table('subheads', function (Blueprint $table) {
            $table->index(['fiscal_year', 'subhead_code'], 'idx_subheads_year_code_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->dropIndex('idx_releases_analytics_lookup');
        });
        Schema::table('subheads', function (Blueprint $table) {
            $table->dropIndex('idx_subheads_year_code_lookup');
        });
    }
};