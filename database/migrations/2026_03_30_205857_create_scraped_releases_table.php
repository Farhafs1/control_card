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
        Schema::create('scraped_releases', function (Blueprint $table) {
            $table->id();
            
            // --- Core Scraped Fields ---
            $table->string('reference_no');
            $table->string('mda_code'); 
            $table->string('mda_name')->nullable(); // Added to help validate without looking up codes
            $table->string('subhead_code');
            $table->date('release_date');
            $table->text('narration');
            $table->decimal('amount', 15, 2);

            // --- Status & Lifecycle Fields ---
            /** * 'status' will store: 'circulating', 'approved', or 'returned'
             * This allows the scraper to "update" the status as the portal changes.
             */
            $table->string('status')->default('circulating'); 
            
            // This flag helps filter for items that just moved to Approved
            $table->boolean('was_notified')->default(false); 

            // --- Metadata for Validation & Fiscal Tracking ---
            $table->boolean('is_salary')->default(false); 
            $table->string('batch_id')->nullable(); 
            
            // Added to align extraction engine parameters with final ledger
            $table->integer('quarter')->nullable()->index()->comment('1, 2, 3, or 4');
            $table->integer('year')->nullable()->index()->comment('Fiscal Year e.g. 2026');
            
            $table->timestamps();

            /**
             * CORRECT COMPOSITE UNIQUE INDEX
             * Includes amount to accommodate multiple allocations under the same reference number.
             */
            $table->unique(
                ['reference_no', 'mda_code', 'subhead_code', 'amount'], 
                'scraped_releases_unique_key'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraped_releases');
    }
};