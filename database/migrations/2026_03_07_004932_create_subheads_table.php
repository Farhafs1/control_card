<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subheads', function (Blueprint $table) {
            $table->id();
            
            // THE LINKS
            $table->foreignId('mda_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            
            // HISTORY & TRACKING
            $table->year('fiscal_year')->index(); // Tag every subhead to a year
            
            // DATA COLUMNS
            $table->string('mda_code'); 
            $table->string('subhead_code'); 
            $table->text('description'); 
            
            // FINANCIALS (Increased precision to 20,2 for large government budgets)
            $table->decimal('approved_provision', 20, 2)->default(0); 
            $table->decimal('virement_provision', 20, 2)->default(0); // For budget shifts
            $table->decimal('supplementary_provision', 20, 2)->default(0); // Mid-year additions
            $table->decimal('additional_provision', 20, 2)->default(0); // Legacy/Other additions
            
            $table->timestamps();

            /**
             * THE UNIQUE CONSTRAINT
             * Includes fiscal_year so the same code/description can repeat annually.
             * Includes description so one MDA can have the same code for different projects.
             */
            $table->unique(
                ['mda_code', 'subhead_code', 'description', 'fiscal_year'], 
                'unique_budget_line_yearly'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subheads');
    }
};