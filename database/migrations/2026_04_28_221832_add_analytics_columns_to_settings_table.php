<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Adding Opening Balance for Revenue Analysis
            $table->decimal('opening_balance', 20, 2)->default(0.00)->after('fiscal_year');
            
            // Adding Expected Revenue (Budget Target) for Variance Analysis
            $table->decimal('expected_revenue', 20, 2)->default(0.00)->after('opening_balance');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'expected_revenue']);
        });
    }
};