<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Mda;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users FIRST 
        // We use updateOrCreate so we can run this multiple times safely
        
        $admin = User::updateOrCreate(
            ['email' => 'admin@budget.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('123'),
                'role' => 'admin',
                'staff_no' => 'ADMIN1',
            ]
        );

        $bo1 = User::updateOrCreate(
            ['email' => 'bo1@budget.com'],
            [
                'name' => 'Bukar Budget',
                'password' => Hash::make('123'),
                'role' => 'officer',
                'staff_no' => 'BO1',
            ]
        );

        $bo2 = User::updateOrCreate(
            ['email' => 'bo2@budget.com'],
            [
                'name' => 'Fatima Finance',
                'password' => Hash::make('123'),
                'role' => 'officer',
                'staff_no' => 'BO2',
            ]
        );

        // 2. Create Initial Settings
        // Do this before MDAs in case MDA creation triggers a log that checks settings
        Setting::updateOrCreate(
            ['fiscal_year' => 2026],
            [
                'app_name' => 'Budget Control System',
                'state_name' => 'Katsina State',
                'is_current_year' => true,
                'budget_status' => 'active',
            ]
        );

        // 3. Create MDAs
        Mda::updateOrCreate(
            ['mda_code' => '0123001001'],
            [
                'user_id' => $bo1->id, 
                'name' => 'Ministry of Health',
                'mda_secret_code' => '98',
                'sector' => 'Social',
            ]
        );

        Mda::updateOrCreate(
            ['mda_code' => '0517001001'],
            [
                'user_id' => $bo2->id, 
                'name' => 'Ministry of Education',
                'mda_secret_code' => '85',
                'sector' => 'Social',
            ]
        );
    }
}