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
            // Added mda_code here so it's searchable directly
            $table->string('mda_code'); 
            $table->string('subhead_code'); 
            $table->text('description'); 
            
            // FINANCIALS
            $table->decimal('approved_provision', 15, 2)->default(0); 
            $table->decimal('additional_provision', 15, 2)->default(0);
            
            $table->timestamps();

            // THE FIX: Composite Unique Constraint
            // This ensures that the combination of MDA Code + Subhead Code is unique.
            // This is safer for budget tracking than using mda_id.
            $table->unique(['mda_code', 'subhead_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subheads');
    }
};