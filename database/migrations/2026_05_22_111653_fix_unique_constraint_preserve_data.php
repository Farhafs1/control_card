<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if we're using SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            
            // Start transaction
            DB::beginTransaction();
            
            try {
                // 1. Create a backup table
                echo "Creating backup...\n";
                DB::statement('CREATE TABLE scraped_releases_backup AS SELECT * FROM scraped_releases');
                
                // 2. Drop the old table
                echo "Dropping old table...\n";
                Schema::dropIfExists('scraped_releases');
                
                // 3. Create new table with correct constraint
                echo "Creating new table with correct constraint...\n";
                Schema::create('scraped_releases', function (Blueprint $table) {
                    $table->id();
                    $table->string('reference_no');
                    $table->string('mda_code'); 
                    $table->string('mda_name')->nullable();
                    $table->string('subhead_code');
                    $table->date('release_date');
                    $table->text('narration');
                    $table->decimal('amount', 15, 2);
                    $table->string('status')->default('circulating'); 
                    $table->boolean('was_notified')->default(false); 
                    $table->boolean('is_salary')->default(false); 
                    $table->string('batch_id')->nullable(); 
                    $table->timestamps();
                    
                    // CORRECT: Include amount in unique constraint
                    $table->unique(['reference_no', 'mda_code', 'subhead_code', 'amount'], 'scraped_releases_unique_key');
                });
                
                // 4. Restore data from backup
                echo "Restoring data...\n";
                DB::statement('INSERT INTO scraped_releases 
                    (id, reference_no, mda_code, mda_name, subhead_code, release_date, 
                     narration, amount, status, was_notified, is_salary, batch_id, created_at, updated_at)
                    SELECT id, reference_no, mda_code, mda_name, subhead_code, release_date, 
                           narration, amount, status, was_notified, is_salary, batch_id, created_at, updated_at
                    FROM scraped_releases_backup');
                
                // 5. Drop backup table
                Schema::dropIfExists('scraped_releases_backup');
                
                DB::commit();
                echo "✅ Migration completed successfully! Data preserved.\n";
                
            } catch (\Exception $e) {
                DB::rollBack();
                echo "❌ Migration failed: " . $e->getMessage() . "\n";
                throw $e;
            }
            
        } else {
            // For MySQL/PostgreSQL - simpler approach
            Schema::table('scraped_releases', function (Blueprint $table) {
                // Drop the old unique constraint if it exists
                $table->dropUnique('unique_scraped_release');
                // Add new one with amount
                $table->unique(['reference_no', 'mda_code', 'subhead_code', 'amount'], 'scraped_releases_unique_key');
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Restore from backup if exists
            if (Schema::hasTable('scraped_releases_backup')) {
                Schema::dropIfExists('scraped_releases');
                Schema::rename('scraped_releases_backup', 'scraped_releases');
            } else {
                Schema::table('scraped_releases', function (Blueprint $table) {
                    $table->dropUnique('scraped_releases_unique_key');
                    $table->unique(['reference_no', 'mda_code', 'subhead_code'], 'unique_scraped_release');
                });
            }
        } else {
            Schema::table('scraped_releases', function (Blueprint $table) {
                $table->dropUnique('scraped_releases_unique_key');
                $table->unique(['reference_no', 'mda_code', 'subhead_code'], 'unique_scraped_release');
            });
        }
    }
};