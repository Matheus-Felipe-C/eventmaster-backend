<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultStaffSeeder extends Seeder
{
    /**
     * Seed one default "staff" account only when no staff users exist.
     */
    public function run(): void
    {
        $role = Role::query()->where('name', 'staff')->first();

        if (! $role) {
            return;
        }

        if (User::query()->where('id_role', $role->id)->exists()) {
            return;
        }

        User::query()->create([
            'id_role' => $role->id,
            'name' => env('DEFAULT_STAFF_NAME', 'Default Staff'),
            'cpf' => env('DEFAULT_STAFF_CPF', '333.333.333-33'),
            'email' => env('DEFAULT_STAFF_EMAIL', 'staff@example.com'),
            'password' => Hash::make(env('DEFAULT_STAFF_PASSWORD', 'password')),
        ]);
    }
}
