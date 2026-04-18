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
        Schema::table('releases', function (Blueprint $table) {
            // Adding 'after' makes the database table easier to read manually
            $table->foreignId('user_id')
                ->nullable()
                ->after('mda_id') 
                ->constrained()
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            // 1. Drop the foreign key constraint first
            $table->dropForeign(['user_id']); 
            
            // 2. Then drop the column
            $table->dropColumn('user_id');
        });
    }
};