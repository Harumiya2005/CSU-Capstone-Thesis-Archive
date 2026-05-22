<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema; // <-- We need to import Schema
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();

        // 2. Safely clear the tables
        DB::table('users')->truncate();

        // (If you have dummy data in audit_logs, uncomment the line below to clear it too)
        // DB::table('audit_logs')->truncate();

        // 3. Turn foreign key checks back on!
        Schema::enableForeignKeyConstraints();

        // 4. Create the Admin User (John Doe)
        DB::table('users')->insert([
            'identifier' => 'admin@carsu.edu.ph',
            'name' => 'John Doe',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'course' => null,
            'year_level' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 5. Create the Student User (Jane Doe)
        DB::table('users')->insert([
            'identifier' => '231-00000',
            'name' => 'Jane Doe',
            'password' => Hash::make('password'),
            'role' => 'student',
            'course' => 'BSIS',
            'year_level' => 3,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
