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
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            
            // Core Identifiers (Strings for codes, IDs for database links)
            $table->string('mda_code')->index();
            $table->string('subhead_code')->index();
            $table->foreignId('mda_id')->constrained()->onDelete('cascade');
            $table->foreignId('subhead_id')->constrained()->onDelete('cascade');

            // Transaction Details
            $table->date('release_date');
            
            // REMOVED ->unique() here to allow bulk releases with one reference number
            $table->string('reference_no')->index(); 
            
            $table->decimal('amount', 20, 2);
            $table->text('narration')->nullable();
            
            $table->timestamps();

            /**
             * COMPOSITE UNIQUE INDEX
             * This allows the same reference_no to exist multiple times, 
             * but prevents the EXACT same transaction from being duplicated.
             */
            $table->unique(
                ['mda_id', 'subhead_id', 'release_date', 'amount', 'reference_no'], 
                'unique_release_transaction'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};