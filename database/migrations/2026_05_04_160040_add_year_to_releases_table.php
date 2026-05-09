<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the column safely
        if (!Schema::hasColumn('releases', 'year')) {
            Schema::table('releases', function (Blueprint $table) {
                $table->integer('year')->nullable()->after('amount')->index();
            });
        }

        // 2. Populate existing rows from 'release_date'
        DB::table('releases')->get()->each(function ($release) {
            if (isset($release->release_date) && $release->release_date) {
                $year = date('Y', strtotime($release->release_date));
                DB::table('releases')
                    ->where('id', $release->id)
                    ->update(['year' => $year]);
            }
        });

        // 3. Finalize: Make it non-nullable now that data is populated
        Schema::table('releases', function (Blueprint $table) {
            $table->integer('year')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};