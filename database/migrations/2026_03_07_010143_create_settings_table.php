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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            
            // BUDGET CONTROLS & HISTORICAL TAGGING
            $table->year('fiscal_year')->default(2026)->unique();
            
            // ANALYSIS AND METRICS (Integrated here right after fiscal_year)
            $table->decimal('opening_balance', 20, 2)->default(0.00)->comment('Opening Balance for Revenue Analysis');
            $table->decimal('expected_revenue', 20, 2)->default(0.00)->comment('Expected Revenue Target for Variance Analysis');

            $table->boolean('is_current_year')->default(true); // To toggle between active/archived years
            $table->string('budget_status')->default('active'); 
            
            // BRANDING
            $table->string('app_name')->default('Budget Management System');
            $table->string('logo_path')->nullable();
            $table->string('state_name')->nullable();
            
            // SYSTEM PREFERENCES
            $table->string('currency_symbol')->default('₦');
            $table->boolean('allow_overspending')->default(false);
            
            // SCRAPER CREDENTIALS
            $table->string('scraper_url')->nullable();
            $table->string('scraper_username')->nullable();
            $table->string('scraper_password')->nullable();
            $table->boolean('require_login')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};