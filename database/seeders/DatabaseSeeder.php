<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create the Admin
        $admin = \App\Models\User::create([
            'name' => 'System Admin',
            'email' => 'admin@budget.com',
            'password' => bcrypt('123'),
            'role' => 'admin',
            'staff_no' => 'ADMIN1',
        ]);

        // 2. Create Budget Officer 1
        $bo1 = \App\Models\User::create([
            'name' => 'Bukar Budget',
            'email' => 'bo1@budget.com',
            'password' => bcrypt('123'),
            'role' => 'officer',
            'staff_no' => 'BO1',
        ]);

        // 3. Create Budget Officer 2
        $bo2 = \App\Models\User::create([
            'name' => 'Fatima Finance',
            'email' => 'bo2@budget.com',
            'password' => bcrypt('123'),
            'role' => 'officer',
            'staff_no' => 'BO2',
        ]);

        // 4. Create MDAs and assign them to the BOs
        \App\Models\Mda::create([
            'user_id' => $bo1->id, // Assigned to BO1
            'mda_code' => '0123001001',
            'name' => 'Ministry of Health',
            'mda_secret_code' => '98',
            'sector' => 'Social',
        ]);

        \App\Models\Mda::create([
            'user_id' => $bo2->id, // Assigned to BO2
            'mda_code' => '0517001001',
            'name' => 'Ministry of Education',
            'mda_secret_code' => '85',
            'sector' => 'Social',
        ]);

            // Also create the initial settings
        \App\Models\Setting::create([
            'fiscal_year' => 2026,
            'app_name' => 'Budget Control System',
            'state_name' => 'Katsina State',
        ]);
    }
}
