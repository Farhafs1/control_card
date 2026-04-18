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
        Schema::table('subheads', function (Blueprint $table) {
            // 1. Drop the old unique index that is causing the error
            // Note: In SQLite, you often have to drop the whole table, but for 
            // MySQL/PostgreSQL use:
            $table->dropUnique(['mda_code', 'subhead_code']);

            // 2. Create the new one that includes description
            $table->unique(['mda_code', 'subhead_code', 'description'], 'unique_budget_line');
        });
    }

    public function down(): void
    {
        Schema::table('subheads', function (Blueprint $table) {
            $table->dropUnique('unique_budget_line');
            $table->unique(['mda_code', 'subhead_code']);
        });
    }
};
