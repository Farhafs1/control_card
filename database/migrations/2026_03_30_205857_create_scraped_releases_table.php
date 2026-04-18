<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraped_releases', function (Blueprint $table) {
            $table->id();
            
            // --- Core Scraped Fields ---
            $table->string('reference_no');
            $table->string('mda_code'); 
            $table->string('mda_name')->nullable(); // NEW: Added to help you validate without looking up codes
            $table->string('subhead_code');
            $table->date('release_date');
            $table->text('narration');
            $table->decimal('amount', 15, 2);

            // --- Status & Lifecycle Fields ---
            /** * 'status' will store: 'circulating', 'approved', or 'returned'
             * This allows the scraper to "update" the status as the portal changes.
             */
            $table->string('status')->default('circulating'); 
            
            // This flag helps you filter for items that just moved to Approved
            $table->boolean('was_notified')->default(false); 

            // --- Metadata for Validation ---
            $table->boolean('is_salary')->default(false); 
            $table->string('batch_id')->nullable(); 
            
            // --- The "Safety Shield" ---
            // Keeps the unique index so updateOrCreate() knows exactly which row to target
            $table->unique(['reference_no', 'mda_code', 'subhead_code'], 'unique_scraped_release');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraped_releases');
    }
};