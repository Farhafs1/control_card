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
        Schema::create('pending_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mda_id')->constrained();
            $table->foreignId('subhead_id')->constrained();
            $table->string('mda_code');
            $table->string('subhead_code');
            $table->date('release_date');
            $table->string('reference_no');
            $table->decimal('amount', 20, 2);
            $table->text('narration')->nullable();
            $table->string('status')->default('pending'); // pending, confirmed, discarded
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_verifications');
    }
};
