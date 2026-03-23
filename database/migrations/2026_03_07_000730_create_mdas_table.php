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
        Schema::create('mdas', function (Blueprint $table) {
            $table->id();
            
            // THE ACCESS LINK: Connects the MDA to a User (Budget Officer)
            // We use 'nullable' because an MDA might not be assigned to anyone yet.
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // IDENTIFICATION
            $table->string('mda_code')->unique(); // e.g., 0123001001
            $table->string('name');               // e.g., Ministry of Health
            $table->string('mda_secret_code')->unique()->nullable(); // Your internal reference code
            
            // CATEGORIZATION
            $table->string('sector');             // e.g., Administrative, Economic, Law & Justice
            
            // STATUS
            // This handles your requirement for departments changing to ministries
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mdas');
    }
};
