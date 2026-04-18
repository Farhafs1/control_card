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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // e.g., 'Created', 'Updated', 'Deleted', 'Login'
            $table->string('module'); // e.g., 'Budget', 'Expenditure', 'User'
            $table->text('description'); // e.g., 'Updated Subhead 22020101 amount from 10m to 12m'
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
