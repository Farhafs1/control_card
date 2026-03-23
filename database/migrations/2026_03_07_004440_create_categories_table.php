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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            
            // THE LINK TO MDA: One MDA has 4 categories
            $table->foreignId('mda_id')->constrained()->onDelete('cascade');
            
            // THE TYPE: 'Revenue', 'Personnel', 'Overhead', 'Capital'
            // We use string, but you can also use enum if you want to restrict it
            $table->string('type'); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};