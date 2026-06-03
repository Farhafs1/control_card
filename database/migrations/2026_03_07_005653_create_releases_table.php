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
            
            // Core Identifiers
            $table->string('mda_code')->index();
            $table->string('subhead_code')->index();
            $table->foreignId('mda_id')->constrained()->onDelete('cascade');
            $table->foreignId('subhead_id')->constrained()->onDelete('cascade');
            
            // Integrated User ID
            $table->foreignId('user_id')
                ->nullable() 
                ->constrained()
                ->onDelete('set null');

            // Transaction Details
            $table->date('release_date');
            $table->string('reference_no')->index();
            $table->decimal('amount', 20, 2);
            $table->text('narration')->nullable();
            
            // Transaction Cancellation Statuses
            $table->boolean('is_cancelled')->default(false);
            $table->text('cancelled_reason')->nullable();
            
            // Fiscal Tracking Dimensions
            $table->integer('quarter')->comment('1, 2, 3, or 4');
            $table->integer('year')->comment('Fiscal Year e.g. 2026');
            
            $table->timestamps();

            /**
             * OPTIMIZED ANALYTICS INDEX
             * Covering index for the Parallel Engine's core filtering logic.
             */
            $table->index(['is_cancelled', 'quarter', 'subhead_id'], 'idx_releases_analytics_lookup');

            /**
             * COMPOSITE UNIQUE INDEX
             * Prevents double-posting the exact same transaction row.
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