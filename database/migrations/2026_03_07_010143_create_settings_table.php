<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            
            // BUDGET CONTROLS
            $table->year('fiscal_year')->default(2026);
            $table->string('budget_status')->default('active'); // e.g., 'open', 'closed', 'provisional'
            
            // BRANDING
            $table->string('app_name')->default('Budget Management System');
            $table->string('logo_path')->nullable(); // Storage path for the logo
            $table->string('state_name')->nullable(); // e.g., Katsina State Government
            
            // SYSTEM PREFERENCES
            $table->string('currency_symbol')->default('₦');
            $table->boolean('allow_overspending')->default(false); // Safety switch
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};