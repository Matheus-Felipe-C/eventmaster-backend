<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            DefaultUserSeeder::class,
            DefaultOrganizerSeeder::class,
            DefaultStaffSeeder::class,
            EventCategorySeeder::class,
            LocalSeeder::class,
            TicketTypeSeeder::class,
        ]);
    }
}
