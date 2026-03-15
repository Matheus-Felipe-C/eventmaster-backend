<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::insert([
            ['name' => 'user'],
            ['name' => 'admin'],
            ['name' => 'organizer'],
            ['name' => 'staff'],
        ]);
    }
}
