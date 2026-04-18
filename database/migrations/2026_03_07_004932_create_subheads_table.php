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
            
            // DATA COLUMNS
            $table->string('mda_code'); 
            $table->string('subhead_code'); 
            $table->text('description'); 
            
            // FINANCIALS
            $table->decimal('approved_provision', 15, 2)->default(0); 
            $table->decimal('additional_provision', 15, 2)->default(0);
            
            $table->timestamps();

            /**
             * THE FIX: 
             * We removed ['mda_code', 'subhead_code'] unique constraint.
             * We now use Description as part of the unique check. 
             * This allows KASEDA to have subhead 22020712 multiple times 
             * as long as the descriptions are different.
             */
            $table->unique(['mda_code', 'subhead_code', 'description'], 'unique_budget_line');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subheads');
    }
};